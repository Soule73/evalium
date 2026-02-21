<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Contracts\Services\EnrollmentServiceInterface;
use App\Contracts\Services\UserManagementServiceInterface;
use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStoreEnrollmentRequest;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Http\Requests\Admin\TransferStudentRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests;

    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
        private readonly EnrollmentRepositoryInterface $enrollmentQueryService,
        private readonly UserManagementServiceInterface $userManagementService
    ) {}

    /**
     * Display a listing of enrollments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Enrollment::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'class_id', 'status']
        );

        $data = $this->enrollmentQueryService->getEnrollmentsForIndex($selectedYearId, $filters, $perPage);

        return Inertia::render('Admin/Enrollments/Index', $data);
    }

    /**
     * Show the form for creating a new enrollment.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Enrollment::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        return Inertia::render('Admin/Enrollments/Create', [
            'selectedYearId' => $selectedYearId,
        ]);
    }

    /**
     * Store a newly created enrollment.
     */
    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        try {
            $this->enrollmentService->enrollStudent(
                $request->integer('student_id'),
                $request->integer('class_id')
            );

            $this->handleEnrollmentCredentials($request);

            return redirect()
                ->route('admin.enrollments.index')
                ->flashSuccess(__('messages.enrollment_created'));
        } catch (EnrollmentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Create a student in the context of enrollment (without sending credentials immediately).
     *
     * Stores credentials in session for optional later sending after enrollment confirmation.
     */
    public function createStudent(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        ['user' => $user, 'password' => $password] = $this->userManagementService->store([
            ...$validated,
            'role' => 'student',
            'send_credentials' => false,
        ]);

        session()->put('new_user_credentials', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
        ]);

        session()->put('pending_enrollment_credentials', [
            'user_id' => $user->id,
            'password' => $password,
        ]);

        $credentialMap = session()->get('enrollment_credential_map', []);
        $credentialMap[$user->id] = $password;
        session()->put('enrollment_credential_map', $credentialMap);

        session()->flash('has_new_user', true);

        return back()->flashSuccess(__('messages.user_created'));
    }

    /**
     * Bulk enroll multiple students into a class in a single request.
     */
    public function bulkStore(BulkStoreEnrollmentRequest $request): JsonResponse
    {
        $classId = $request->integer('class_id');
        $studentIds = $request->array('student_ids');
        $newStudentIds = $request->array('new_student_ids', []);
        $sendCredentials = $request->boolean('send_credentials');

        $credentialMap = session()->pull('enrollment_credential_map', []);

        $class = ClassModel::findOrFail($classId);

        $enrolled = [];
        $failed = [];

        foreach ($studentIds as $studentId) {
            try {
                $enrollment = $this->enrollmentService->enrollStudent((int) $studentId, $classId);
                $enrollment->load('student:id,name,email');

                $password = null;
                $isNew = in_array((int) $studentId, array_map('intval', $newStudentIds));

                if ($isNew && isset($credentialMap[$studentId])) {
                    $password = $credentialMap[$studentId];

                    if ($sendCredentials) {
                        $enrollment->student->notify(
                            new \App\Notifications\UserCredentialsNotification($password, 'student')
                        );
                    }
                }

                $enrolled[] = [
                    'student_id' => $enrollment->student_id,
                    'student_name' => $enrollment->student->name,
                    'student_email' => $enrollment->student->email,
                    'enrollment_id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'password' => $password,
                ];
            } catch (\App\Exceptions\EnrollmentException $e) {
                $student = User::find($studentId);
                $failed[] = [
                    'student_id' => $studentId,
                    'student_name' => $student?->name ?? "#$studentId",
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'class_name' => $class->name,
            'enrolled' => $enrolled,
            'failed' => $failed,
        ]);
    }

    /**
     * Search students available for enrollment.
     * Excludes students already actively enrolled in any class of the selected academic year.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->value();
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPage = min($request->integer('per_page', 15), 100);

        $students = User::role('student')
            ->select(['id', 'name', 'email', 'avatar'])
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            }))
            ->whereDoesntHave('enrollments', function ($q) use ($selectedYearId) {
                $q->where('status', '!=', 'withdrawn')
                    ->when(
                        $selectedYearId,
                        fn ($q) => $q->whereHas('class', fn ($q) => $q->where('academic_year_id', $selectedYearId))
                    );
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($students);
    }

    /**
     * Search classes available for enrollment.
     */
    public function searchClasses(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->value();
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPage = min($request->integer('per_page', 15), 100);

        $classes = ClassModel::forAcademicYear($selectedYearId)
            ->with(['level:id,name,description', 'academicYear:id,name'])
            ->withCount([
                'enrollments as active_enrollments_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($classes);
    }

    /**
     * Transfer a student to a different class.
     */
    public function transfer(TransferStudentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        try {
            $newEnrollment = $this->enrollmentService->transferStudent(
                $enrollment,
                $request->integer('new_class_id')
            );

            return redirect()
                ->route('admin.classes.students.show', [
                    'class' => $newEnrollment->class_id,
                    'enrollment' => $newEnrollment->id,
                ])
                ->flashSuccess(__('messages.student_transferred'));
        } catch (EnrollmentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Withdraw a student from their class.
     */
    public function withdraw(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('update', $enrollment);

        $this->enrollmentService->withdrawStudent($enrollment);

        return back()->flashSuccess(__('messages.student_withdrawn'));
    }

    /**
     * Reactivate a withdrawn enrollment.
     */
    public function reactivate(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('update', $enrollment);

        try {
            $this->enrollmentService->reactivateEnrollment($enrollment);

            return back()->flashSuccess(__('messages.enrollment_reactivated'));
        } catch (EnrollmentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('delete', $enrollment);

        $enrollment->delete();

        return redirect()
            ->route('admin.enrollments.index')
            ->flashSuccess(__('messages.enrollment_deleted'));
    }

    /**
     * Send credentials notification if requested after enrollment of a newly created student.
     */
    private function handleEnrollmentCredentials(StoreEnrollmentRequest $request): void
    {
        if (! $request->boolean('send_credentials')) {
            return;
        }

        $enrollmentCredentials = session()->pull('pending_enrollment_credentials');

        if (! $enrollmentCredentials) {
            return;
        }

        $student = User::find($enrollmentCredentials['user_id']);

        if (! $student) {
            return;
        }

        $student->notify(new UserCredentialsNotification($enrollmentCredentials['password'], 'student'));

        session()->put('new_user_credentials', [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'password' => $enrollmentCredentials['password'],
        ]);

        session()->flash('has_new_user', true);
    }
}
