<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Contracts\Services\EnrollmentServiceInterface;
use App\Contracts\Services\UserManagementServiceInterface;
use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Http\Requests\Admin\TransferStudentRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\Enrollment;
use App\Models\User;
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
        $formData = $this->enrollmentQueryService->getCreateFormData($selectedYearId);

        return Inertia::render('Admin/Enrollments/Create', $formData);
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

            return redirect()
                ->route('admin.enrollments.index')
                ->flashSuccess(__('messages.enrollment_created'));
        } catch (EnrollmentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Quick-create a student for inline enrollment form.
     */
    public function storeQuickStudent(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        $student = $this->userManagementService->store([
            ...$validated,
            'role' => 'student',
        ]);

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'avatar' => $student->avatar,
        ], 201);
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
}
