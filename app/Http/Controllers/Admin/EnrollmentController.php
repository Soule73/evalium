<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Http\Requests\Admin\TransferStudentRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Admin\EnrollmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of enrollments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Enrollment::class);

        $filters = $request->only(['search', 'class_id', 'status']);
        $perPage = $request->input('per_page', 15);

        $enrollments = Enrollment::query()
            ->with(['student', 'class.academicYear', 'class.level'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['class_id'] ?? null, fn($query, $classId) => $query->where('class_id', $classId))
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->orderBy('enrolled_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $classes = ClassModel::with('academicYear')->orderBy('name')->get();

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $filters,
            'classes' => $classes,
        ]);
    }

    /**
     * Show the form for creating a new enrollment.
     */
    public function create(): Response
    {
        $this->authorize('create', Enrollment::class);

        $classes = ClassModel::with('academicYear')->orderBy('name')->get();
        $students = User::role('student')->orderBy('name')->get();

        return Inertia::render('Admin/Enrollments/Create', [
            'classes' => $classes,
            'students' => $students,
        ]);
    }

    /**
     * Store a newly created enrollment.
     */
    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $student = User::findOrFail($request->input('student_id'));
        $class = ClassModel::findOrFail($request->input('class_id'));

        try {
            $this->enrollmentService->enrollStudent($student, $class);

            return redirect()
                ->route('admin.enrollments.index')
                ->flashSuccess(__('messages.enrollment_created'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Display the specified enrollment.
     */
    public function show(Enrollment $enrollment): Response
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['student', 'class.academicYear', 'class.level']);

        return Inertia::render('Admin/Enrollments/Show', [
            'enrollment' => $enrollment,
        ]);
    }

    /**
     * Transfer a student to a different class.
     */
    public function transfer(TransferStudentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $newClass = ClassModel::findOrFail($request->input('new_class_id'));

        try {
            $newEnrollment = $this->enrollmentService->transferStudent($enrollment, $newClass);

            return redirect()
                ->route('admin.enrollments.show', $newEnrollment)
                ->flashSuccess(__('messages.student_transferred'));
        } catch (\InvalidArgumentException $e) {
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
        } catch (\InvalidArgumentException $e) {
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
