<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesIndexRequests;
use App\Http\Traits\HasTeacherClassRouteContext;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\Core\GradeCalculationService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles teacher access to student grade data within a class context.
 */
class TeacherClassStudentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests, HasTeacherClassRouteContext;

    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly ClassRepositoryInterface $classRepository,
    ) {}

    /**
     * @return array<string, string|null>
     */
    private function buildRouteContext(): array
    {
        return $this->buildTeacherClassRouteContext();
    }

    /**
     * Display a paginated list of students enrolled in a class taught by the teacher.
     */
    public function index(Request $request, ClassModel $class): Response
    {
        $this->authorize('view', $class);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'status']
        );

        $enrollments = $this->classRepository->getPaginatedEnrollments(
            $class,
            array_merge($filters, ['per_page' => $perPage, 'page' => $request->input('page', 1)])
        );

        return Inertia::render('Classes/Students/Index', [
            'class' => $class->load('level', 'academicYear'),
            'enrollments' => $enrollments,
            'filters' => $filters,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Display a student's grade breakdown within their class context.
     */
    public function show(Request $request, ClassModel $class, Enrollment $enrollment): Response
    {
        abort_if($enrollment->class_id !== $class->id, 404);

        $this->authorize('view', $class);

        $enrollment->loadMissing(['student', 'class.level', 'class.academicYear']);

        $student = $enrollment->student;
        $gradeBreakdown = $this->gradeCalculationService->getGradeBreakdown($student, $class);

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $allSubjects = collect($gradeBreakdown['subjects']);

        $paginatedSubjects = new LengthAwarePaginator(
            $allSubjects->forPage($page, $perPage)->values(),
            $allSubjects->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $overallStats = collect($gradeBreakdown)->except('subjects')->all();
        $overallStats['total_assessments'] = $allSubjects->sum('assessments_count');
        $overallStats['completed_assessments'] = $allSubjects->sum('completed_count');

        return Inertia::render('Classes/Students/Show', [
            'class' => $class,
            'enrollment' => $enrollment,
            'subjects' => $paginatedSubjects,
            'overallStats' => $overallStats,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Display all assessment assignments for a student's enrollment.
     */
    public function assignments(Request $request, ClassModel $class, Enrollment $enrollment): Response
    {
        abort_if($enrollment->class_id !== $class->id, 404);

        $this->authorize('view', $class);

        $enrollment->loadMissing(['student', 'class.level']);

        $filters = $request->only(['search', 'class_subject_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $assignments = $this->gradeCalculationService->getEnrollmentAssignments(
            $enrollment,
            $filters,
            $perPage
        );

        $subjects = $this->enrollmentRepository->getClassSubjectsForEnrollment($enrollment);

        return Inertia::render('Classes/Students/Assignments/Index', [
            'class' => $class,
            'enrollment' => $enrollment,
            'assignments' => $assignments,
            'subjects' => $subjects,
            'filters' => $filters,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }
}
