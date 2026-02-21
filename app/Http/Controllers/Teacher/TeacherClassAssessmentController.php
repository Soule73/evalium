<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Contracts\Repositories\TeacherClassRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Repositories\Teacher\GradingRepository;
use App\Services\Core\AssessmentStatsService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles teacher-scoped assessment actions within a class context.
 */
class TeacherClassAssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly TeacherClassRepositoryInterface $classQueryService,
        private readonly TeacherAssessmentRepositoryInterface $assessmentQueryService,
        private readonly GradingRepository $gradingQueryService,
        private readonly AssessmentStatsService $assessmentStatsService,
    ) {}

    /**
     * @return array<string, string|null>
     */
    private function buildClassRouteContext(): array
    {
        return [
            'role' => 'teacher',
            'indexRoute' => 'teacher.classes.index',
            'showRoute' => 'teacher.classes.show',
            'editRoute' => null,
            'deleteRoute' => null,
            'assessmentsRoute' => 'teacher.classes.assessments',
            'subjectShowRoute' => null,
            'studentShowRoute' => 'teacher.classes.students.show',
            'studentIndexRoute' => null,
            'studentAssignmentsRoute' => 'teacher.classes.students.assignments',
            'assessmentShowRoute' => 'teacher.classes.assessments.show',
            'assessmentGradeRoute' => 'teacher.assessments.grade',
            'assessmentReviewRoute' => 'teacher.assessments.review',
            'assessmentSaveGradeRoute' => 'teacher.assessments.saveGrade',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function buildAssessmentRouteContext(): array
    {
        return [
            'role' => 'teacher',
            'backRoute' => 'teacher.assessments.index',
            'showRoute' => null,
            'reviewRoute' => 'teacher.assessments.review',
            'gradeRoute' => 'teacher.assessments.grade',
            'saveGradeRoute' => 'teacher.assessments.saveGrade',
            'editRoute' => null,
            'publishRoute' => null,
            'unpublishRoute' => null,
            'duplicateRoute' => null,
            'reopenRoute' => null,
        ];
    }

    /**
     * Display all assessments for the teacher within a specific class.
     */
    public function index(Request $request, ClassModel $class): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $this->classQueryService->validateAcademicYearAccess($class, $selectedYearId);

        $filters = $request->only(['search', 'subject_id']);
        $perPage = (int) $request->input('per_page', 15);

        $assessments = $this->classQueryService->getAssessmentsForClass(
            $class,
            $teacherId,
            $filters,
            $perPage
        );

        $subjects = ClassSubject::query()
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->whereNull('valid_to')
            ->with('subject:id,name')
            ->get()
            ->map(fn ($cs) => ['id' => $cs->subject_id, 'name' => $cs->subject?->name])
            ->filter(fn ($s) => $s['name'])
            ->values();

        $class->load(['academicYear', 'level']);

        return Inertia::render('Classes/Assessments', [
            'class' => $class,
            'assessments' => $assessments,
            'filters' => $filters,
            'subjects' => $subjects,
            'routeContext' => $this->buildClassRouteContext(),
        ]);
    }

    /**
     * Display the specified assessment within its class context.
     */
    public function show(Request $request, ClassModel $class, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $this->classQueryService->validateAcademicYearAccess($class, $selectedYearId);

        $perPage = (int) $request->input('per_page', 10);

        $assessment = $this->assessmentQueryService->loadAssessmentDetails($assessment);

        $assignments = $this->gradingQueryService->getAssignmentsWithEnrolledStudents(
            $assessment,
            $request->only(['search']),
            $perPage
        );

        return Inertia::render('Assessments/Show', [
            'assessment' => $assessment,
            'assignments' => $assignments,
            'stats' => $this->assessmentStatsService->calculateAssessmentStats($assessment->id),
            'routeContext' => $this->buildAssessmentRouteContext(),
        ]);
    }
}
