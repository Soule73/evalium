<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ReopenAssignmentRequest;
use App\Http\Requests\Teacher\StoreAssessmentRequest;
use App\Http\Requests\Teacher\UpdateAssessmentRequest;
use App\Http\Traits\HandlesAssessmentViewing;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Repositories\Teacher\GradingRepository;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Services\Core\AssessmentService;
use App\Services\Core\AssessmentStatsService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Teacher\AssignmentExceptionService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teacher Assessment Controller
 *
 * Handles CRUD, publishing, duplication, and assignment reopening for teacher assessments.
 * Show/review/grade/saveGrade are handled by HandlesAssessmentViewing trait.
 */
class AssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesAssessmentViewing;

    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly TeacherAssessmentRepositoryInterface $assessmentQueryService,
        private readonly GradingRepository $gradingQueryService,
        private readonly ScoringService $scoringService,
        private readonly AnswerFormatterService $answerFormatterService,
        private readonly AssignmentExceptionService $assignmentExceptionService,
        private readonly AssessmentStatsService $assessmentStatsService
    ) {}

    protected function resolveAssessmentQueryService(): TeacherAssessmentRepositoryInterface
    {
        return $this->assessmentQueryService;
    }

    protected function afterGradingLoad(Request $request, Assessment $assessment): void
    {
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);
    }

    /**
     * Display a listing of teacher's assessments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessment::class);

        $filters = $request->only(['search', 'class_subject_id', 'class_id', 'type', 'is_published']);
        $perPage = $request->input('per_page', 15);

        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $assessments = $this->assessmentQueryService->getAssessmentsForTeacher(
            $teacherId,
            $selectedYearId,
            $filters,
            $perPage
        );

        $classes = $this->assessmentQueryService
            ->getClassFilterDataForTeacher($teacherId, $selectedYearId)
            ->map(fn ($item) => [
                'id' => $item->class_id,
                'name' => $item->class_name.' - '.$item->level_name.' ('.$item->level_description.')',
            ]);

        return Inertia::render('Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $filters,
            'classes' => $classes,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Show the form for creating a new assessment.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Assessment::class);

        $teacherId = Auth::id();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $classSubjects = $this->assessmentQueryService->getClassSubjectsForTeacher(
            $teacherId,
            $selectedYearId,
            ['class.academicYear', 'class.level']
        );

        return Inertia::render('Teacher/Assessments/Create', [
            'classSubjects' => $classSubjects,
        ]);
    }

    /**
     * Store a newly created assessment.
     */
    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->assessmentService->createAssessment($request->validated());

        return redirect()
            ->route('teacher.assessments.show', $assessment)
            ->flashSuccess(__('messages.assessment_created'));
    }

    /**
     * Show the form for editing the specified assessment.
     */
    public function edit(Request $request, Assessment $assessment): Response
    {
        $this->authorize('update', $assessment);

        $assessment = $this->assessmentQueryService->loadAssessmentForEdit($assessment);

        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $classSubjects = $this->assessmentQueryService->getClassSubjectsForTeacher(
            $teacherId,
            $selectedYearId,
            ['class.academicYear', 'class.level']
        );

        return Inertia::render('Teacher/Assessments/Edit', [
            'assessment' => $assessment,
            'classSubjects' => $classSubjects,
        ]);
    }

    /**
     * Update the specified assessment.
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->assessmentService->updateAssessment($assessment, $request->validated());

        return redirect()
            ->route('teacher.assessments.show', $assessment)
            ->flashSuccess(__('messages.assessment_updated'));
    }

    /**
     * Remove the specified assessment.
     */
    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $this->assessmentService->deleteAssessment($assessment);

        return redirect()
            ->route('teacher.assessments.index')
            ->flashSuccess(__('messages.assessment_deleted'));
    }

    /**
     * Publish the specified assessment.
     */
    public function publish(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $this->assessmentService->publishAssessment($assessment);

        return back()->flashSuccess(__('messages.assessment_published'));
    }

    /**
     * Unpublish the specified assessment.
     */
    public function unpublish(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $this->assessmentService->unpublishAssessment($assessment);

        return back()->flashSuccess(__('messages.assessment_unpublished'));
    }

    /**
     * Duplicate the specified assessment.
     */
    public function duplicate(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $overrides = $request->only(['title', 'scheduled_date']);

        $newAssessment = $this->assessmentService->duplicateAssessment($assessment, $overrides);

        return redirect()
            ->route('teacher.assessments.show', $newAssessment)
            ->flashSuccess(__('messages.assessment_duplicated'));
    }

    /**
     * Reopen an interrupted supervised assignment for a student.
     */
    public function reopenAssignment(
        ReopenAssignmentRequest $request,
        Assessment $assessment,
        AssessmentAssignment $assignment
    ): JsonResponse {
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $check = $this->assignmentExceptionService->canReopen($assignment, $assessment);

        if (! $check['can_reopen']) {
            return response()->json([
                'message' => __('messages.assignment_cannot_reopen_'.$check['reason']),
            ], 422);
        }

        $remainingSeconds = $this->assignmentExceptionService->reopenForStudent(
            $assignment,
            $assessment,
            $request->input('reason')
        );

        return response()->json([
            'message' => __('messages.assignment_reopened'),
            'remaining_seconds' => $remainingSeconds,
        ]);
    }

    /**
     * Build route context array for teacher role.
     *
     * @return array<string, string|null>
     */
    protected function buildRouteContext(): array
    {
        return [
            'role' => 'teacher',
            'backRoute' => 'teacher.assessments.index',
            'showRoute' => 'teacher.assessments.show',
            'reviewRoute' => 'teacher.assessments.review',
            'gradeRoute' => 'teacher.assessments.grade',
            'saveGradeRoute' => 'teacher.assessments.saveGrade',
            'editRoute' => 'teacher.assessments.edit',
            'publishRoute' => 'teacher.assessments.publish',
            'unpublishRoute' => 'teacher.assessments.unpublish',
            'duplicateRoute' => 'teacher.assessments.duplicate',
            'reopenRoute' => 'teacher.assessments.reopen',
            'createRoute' => 'teacher.assessments.create',
        ];
    }
}
