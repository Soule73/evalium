<?php

namespace App\Http\Traits;

use App\Http\Requests\Teacher\SaveManualGradeRequest;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\Teacher\TeacherAssessmentQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared assessment viewing, reviewing, and grading logic.
 *
 * Extracts common show/review/grade/saveGrade methods used by both
 * AdminAssessmentController and TeacherAssessmentController.
 *
 * Requires the using class to have these injected properties:
 * - GradingQueryService $gradingQueryService
 * - AnswerFormatterService $answerFormatterService
 * - ScoringService $scoringService
 *
 * Also requires AuthorizesRequests trait and flash message macros from FlashMessageServiceProvider.
 */
trait HandlesAssessmentViewing
{
    /**
     * @return array<string, string|null>
     */
    abstract protected function buildRouteContext(): array;

    abstract protected function resolveAssessmentQueryService(): TeacherAssessmentQueryService;

    /**
     * Hook called after loading assessment data in review/grade methods.
     * Override in teacher controller to add academic year validation.
     */
    protected function afterGradingLoad(Request $request, Assessment $assessment): void {}

    /**
     * Display the specified assessment with assignments listing.
     */
    public function show(Request $request, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);
        $perPage = (int) $request->input('per_page', 10);

        $assessment = $this->resolveAssessmentQueryService()->loadAssessmentDetails($assessment);

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
     * Display the review interface for a graded assignment (read-only).
     */
    public function review(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
    {
        $this->authorize('view', $assessment);
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
        $this->afterGradingLoad($request, $assessment);

        $assignment->load(['enrollment.student', 'answers.choice']);
        $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

        return Inertia::render('Assessments/Review', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'student' => $assignment->enrollment?->student,
            'userAnswers' => $userAnswers,
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Display the grading interface for a specific student assignment.
     */
    public function grade(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
    {
        $this->authorize('update', $assessment);
        abort_unless($assignment->assessment_id === $assessment->id, 404);

        $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
        $this->afterGradingLoad($request, $assessment);

        $assignment->load(['enrollment.student', 'answers.choice']);
        $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

        return Inertia::render('Assessments/Grade', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'student' => $assignment->enrollment?->student,
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
}
