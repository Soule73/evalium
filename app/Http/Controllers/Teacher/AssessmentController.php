<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ReopenAssignmentRequest;
use App\Http\Requests\Teacher\SaveManualGradeRequest;
use App\Http\Requests\Teacher\StoreAssessmentRequest;
use App\Http\Requests\Teacher\UpdateAssessmentRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Services\Core\AssessmentService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Teacher\AssignmentExceptionService;
use App\Services\Teacher\GradingQueryService;
use App\Services\Teacher\TeacherAssessmentQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HasFlashMessages;

    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly TeacherAssessmentQueryService $assessmentQueryService,
        private readonly GradingQueryService $gradingQueryService,
        private readonly ScoringService $scoringService,
        private readonly AnswerFormatterService $answerFormatterService,
        private readonly AssignmentExceptionService $assignmentExceptionService
    ) {}

    /**
     * Display a listing of teacher's assessments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessment::class);

        $filters = $request->only(['search', 'class_subject_id', 'type', 'is_published']);
        $perPage = $request->input('per_page', 15);

        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $assessments = $this->assessmentQueryService->getAssessmentsForTeacher(
            $teacherId,
            $selectedYearId,
            $filters,
            $perPage
        );

        // $classSubjects = $this->assessmentQueryService->getClassSubjectsForTeacher(
        //     $teacherId,
        //     $selectedYearId
        // );

        return Inertia::render('Teacher/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $filters,
            // 'classSubjects' => $classSubjects,
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
     * Display the specified assessment.
     */
    public function show(Request $request, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);
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
            'routeContext' => $this->buildRouteContext(),
        ]);
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
     * Display the grading interface for a specific student assignment.
     */
    public function grade(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
    {
        $this->authorize('view', $assessment);
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignment->load(['student', 'answers.choice']);
        $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

        return Inertia::render('Assessments/Grade', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'student' => $assignment->student,
            'userAnswers' => $userAnswers,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Display the review interface for a graded assignment (read-only).
     */
    public function review(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
    {
        $this->authorize('view', $assessment);
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignment->load(['student', 'answers.choice']);
        $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

        return Inertia::render('Assessments/Review', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'student' => $assignment->student,
            'userAnswers' => $userAnswers,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Save the grading for a specific student assignment.
     */
    public function saveGrade(SaveManualGradeRequest $request, Assessment $assessment, AssessmentAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $this->scoringService->saveManualGrades(
            $assignment,
            $request->input('scores', []),
            $request->input('teacher_notes')
        );

        return back()->flashSuccess(__('messages.grade_saved'));
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
    private function buildRouteContext(): array
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
        ];
    }
}
