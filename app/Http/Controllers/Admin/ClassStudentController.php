<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\Core\GradeCalculationService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class ClassStudentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests;

    public function __construct(
        private readonly ClassRepositoryInterface $classRepository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly GradeCalculationService $gradeCalculationService
    ) {}

    /**
     * Display a paginated list of students enrolled in a class.
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
            'routeContext' => [
                'role' => 'admin',
                'indexRoute' => 'admin.classes.index',
                'showRoute' => 'admin.classes.show',
                'editRoute' => 'admin.classes.edit',
                'deleteRoute' => 'admin.classes.destroy',
                'assessmentsRoute' => 'admin.classes.assessments',
                'subjectShowRoute' => 'admin.classes.subjects.show',
                'studentShowRoute' => 'admin.classes.students.show',
                'studentIndexRoute' => 'admin.classes.students.index',
                'studentAssignmentsRoute' => 'admin.classes.students.assignments',
                'assessmentShowRoute' => 'admin.classes.assessments.show',
                'assessmentGradeRoute' => 'admin.assessments.grade',
                'assessmentReviewRoute' => 'admin.assessments.review',
                'assessmentSaveGradeRoute' => 'admin.assessments.saveGrade',
            ],
        ]);
    }

    /**
     * Display a student's grade breakdown within their class context.
     */
    public function show(Request $request, ClassModel $class, Enrollment $enrollment): Response
    {
        abort_if($enrollment->class_id !== $class->id, 404);

        $this->authorize('view', $enrollment);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

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

        $showData = $this->enrollmentRepository->getShowData($enrollment, $selectedYearId);

        return Inertia::render('Classes/Students/Show', [
            'class' => $class,
            'enrollment' => $enrollment,
            'subjects' => $paginatedSubjects,
            'overallStats' => $overallStats,
            'classes' => $showData['classes'],
            'routeContext' => [
                'role' => 'admin',
                'indexRoute' => 'admin.classes.index',
                'showRoute' => 'admin.classes.show',
                'editRoute' => 'admin.classes.edit',
                'deleteRoute' => 'admin.classes.destroy',
                'assessmentsRoute' => 'admin.classes.assessments',
                'subjectShowRoute' => 'admin.classes.subjects.show',
                'studentShowRoute' => 'admin.classes.students.show',
                'studentIndexRoute' => 'admin.classes.students.index',
                'studentAssignmentsRoute' => 'admin.classes.students.assignments',
                'assessmentShowRoute' => 'admin.classes.assessments.show',
                'assessmentGradeRoute' => 'admin.assessments.grade',
                'assessmentReviewRoute' => 'admin.assessments.review',
                'assessmentSaveGradeRoute' => 'admin.assessments.saveGrade',
            ],
        ]);
    }

    /**
     * Display all assessment assignments for a student's enrollment.
     */
    public function assignments(Request $request, ClassModel $class, Enrollment $enrollment): Response
    {
        abort_if($enrollment->class_id !== $class->id, 404);

        $this->authorize('view', $enrollment);

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
            'routeContext' => [
                'role' => 'admin',
                'indexRoute' => 'admin.classes.index',
                'showRoute' => 'admin.classes.show',
                'editRoute' => 'admin.classes.edit',
                'deleteRoute' => 'admin.classes.destroy',
                'assessmentsRoute' => 'admin.classes.assessments',
                'subjectShowRoute' => 'admin.classes.subjects.show',
                'studentShowRoute' => 'admin.classes.students.show',
                'studentIndexRoute' => 'admin.classes.students.index',
                'studentAssignmentsRoute' => 'admin.classes.students.assignments',
                'assessmentShowRoute' => 'admin.classes.assessments.show',
                'assessmentGradeRoute' => 'admin.assessments.grade',
                'assessmentReviewRoute' => 'admin.assessments.review',
                'assessmentSaveGradeRoute' => 'admin.assessments.saveGrade',
            ],
        ]);
    }
}
