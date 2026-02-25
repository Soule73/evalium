<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAnswersRequest;
use App\Http\Requests\Student\SecurityViolationRequest;
use App\Http\Requests\Student\UploadFileAnswerRequest;
use App\Models\Answer;
use App\Models\Assessment;
use App\Notifications\AssessmentSubmittedNotification;
use App\Services\Student\FileAnswerService;
use App\Services\Student\StudentAssessmentService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StudentAssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly StudentAssessmentService $assessmentService,
        private readonly FileAnswerService $fileAnswerService
    ) {}

    /**
     * Strip is_correct from all choices to prevent cheating via DevTools.
     */
    private function hideCorrectAnswers(Assessment $assessment): void
    {
        $assessment->questions?->each(function ($question) {
            $question->choices?->each(function ($choice) {
                $choice->makeHidden('is_correct');
            });
        });
    }

    /**
     * Determine whether the student should see correct answers on the results page.
     */
    private function shouldRevealCorrectAnswers(Assessment $assessment, ?object $assignment): bool
    {
        if (! $assignment?->graded_at) {
            return false;
        }

        return $assessment->show_correct_answers;
    }

    /**
     * Display a listing of student's assessments.
     */
    public function index(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $filters = $request->only(['status', 'search', 'class_subject_id']);
        $perPage = $request->input('per_page', 15);

        $result = $this->assessmentService->getStudentAssessmentsForIndex(
            $student,
            $selectedYearId,
            $filters,
            $perPage
        );

        return Inertia::render('Student/Assessments/Index', [
            'assignments' => $result['assignments'],
            'subjects' => $result['subjects'],
            'filters' => $filters,
        ]);
    }

    /**
     * Display the specified assessment for the student.
     */
    public function show(Assessment $assessment): Response
    {
        $student = Auth::user();

        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class.level',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $this->hideCorrectAnswers($assessment);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        $embargoLifted = $assessment->isResultsEmbargoLifted();
        $canViewResults = $assignment->submitted_at !== null
            && $embargoLifted
            && (! $assessment->release_results_after_grading || $assignment->graded_at !== null);

        return Inertia::render('Student/Assessments/Show', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'availability' => $assessment->getAvailabilityStatus(),
            'canViewResults' => $canViewResults,
        ]);
    }

    /**
     * Start an assessment.
     */
    public function start(Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $availability = $assessment->getAvailabilityStatus();

        if (! $availability['available']) {
            return back()->flashError(__('messages.' . $availability['reason']));
        }

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);
        $this->assessmentService->startAssignment($assignment, $assessment);

        return redirect()->route('student.assessments.take', $assessment);
    }

    /**
     * Take/work on an assessment.
     */
    public function take(Assessment $assessment): Response|RedirectResponse
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);
        $this->assessmentService->startAssignment($assignment, $assessment);

        if ($assignment->submitted_at) {
            $redirectRoute = $assessment->isSupervisedMode()
                ? 'student.assessments.result'
                : 'student.assessments.show';

            return redirect()->route($redirectRoute, $assessment);
        }

        if ($this->assessmentService->autoSubmitIfExpired($assignment, $assessment)) {
            return redirect()
                ->route('student.assessments.result', $assessment)
                ->flashError(__('messages.assessment_time_expired'));
        }

        $availability = $assessment->getAvailabilityStatus();

        if (! $availability['available']) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->flashError(__('messages.' . $availability['reason']));
        }

        $assignment->load([
            'assessment.classSubject.class.level',
            'assessment.classSubject.subject',
            'assessment.classSubject.teacher',
            'assessment.questions.choices',
            'answers',
        ]);

        $assessment = $assignment->assessment;

        $remainingSeconds = $this->assessmentService->calculateRemainingSeconds($assignment, $assessment);

        $page = $assessment->isHomeworkMode()
            ? 'Student/Assessments/Work'
            : 'Student/Assessments/Take';

        $props = [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'questions' => $assessment->questions,
            'userAnswers' => $assignment->answers,
            'remainingSeconds' => $remainingSeconds,
        ];

        $this->hideCorrectAnswers($assessment);

        if ($assessment->isHomeworkMode()) {
            $props['fileAnswers'] = $assignment->answers()->whereNotNull('file_path')->get();
        } else {
            $props['assessmentConfig'] = ['devMode' => config('assessment.dev_mode', false)];
        }

        return Inertia::render($page, $props);
    }

    /**
     * Save answers (auto-save during assessment).
     */
    public function saveAnswers(SaveAnswersRequest $request, Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        if ($this->assessmentService->isTimeExpired($assignment, $assessment, withGrace: true)) {
            $this->assessmentService->autoSubmitIfExpired($assignment, $assessment);

            return response()->json(['message' => __('messages.assessment_time_expired')], 409);
        }

        if ($this->assessmentService->isDueDatePassed($assessment)) {
            return response()->json(['message' => __('messages.assessment_due_date_passed')], 409);
        }

        $this->assessmentService->saveAnswers($assignment, $request->input('answers', []));

        return response()->json(['message' => __('messages.answers_saved')]);
    }

    /**
     * Submit answers for an assessment.
     */
    public function submit(SaveAnswersRequest $request, Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return back()->flashError(__('messages.assessment_already_submitted'));
        }

        if ($this->assessmentService->isTimeExpired($assignment, $assessment)) {
            $this->assessmentService->autoSubmitIfExpired($assignment, $assessment);

            return redirect()
                ->route('student.assessments.show', $assessment)
                ->flashError(__('messages.assessment_time_expired'));
        }

        if ($this->assessmentService->isDueDatePassed($assessment)) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->flashError(__('messages.assessment_due_date_passed'));
        }

        $this->assessmentService->submitAssessment($assignment, $assessment, $request->input('answers', []));

        $assessment->loadMissing('classSubject.teacher');
        $teacher = $assessment->classSubject?->teacher;

        if ($teacher) {
            $teacher->notify(new AssessmentSubmittedNotification($assessment, $assignment));
        }

        if ($assessment->isSupervisedMode() && ! $assessment->isResultsEmbargoLifted()) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->flashSuccess(__('messages.assessment_submitted'));
        }

        return redirect()
            ->route('student.assessments.result', $assessment)
            ->flashSuccess(__('messages.assessment_submitted'));
    }

    /**
     * Display assessment results.
     */
    public function results(Assessment $assessment): Response|RedirectResponse
    {
        $student = Auth::user();

        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class.level',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $assignment = $this->assessmentService->getAssignmentForResults($student, $assessment);

        if (! $assignment || ! $assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        if ($assessment->isSupervisedMode() && ! $assessment->isResultsEmbargoLifted()) {
            return redirect()->route('student.assessments.show', $assessment)
                ->flashInfo(__('messages.results_under_embargo'));
        }

        if (! $assessment->show_results_immediately && ! $assignment->graded_at) {
            return redirect()->route('student.assessments.show', $assessment)
                ->flashInfo(__('messages.results_not_available_yet'));
        }

        $canRevealAnswers = $this->shouldRevealCorrectAnswers($assessment, $assignment);

        if (! $canRevealAnswers) {
            $this->hideCorrectAnswers($assessment);

            $assignment->answers->each(function ($answer) {
                $answer->choice?->makeHidden('is_correct');
            });
        }

        $userAnswers = $this->assessmentService->formatUserAnswers($assignment->answers);

        if (! $canRevealAnswers) {
            foreach ($userAnswers as &$answer) {
                if (isset($answer['choice'])) {
                    unset($answer['choice']['is_correct']);
                }
                if (isset($answer['choices'])) {
                    foreach ($answer['choices'] as &$answerChoice) {
                        if (isset($answerChoice['choice'])) {
                            unset($answerChoice['choice']['is_correct']);
                        }
                    }
                }
            }
            unset($answer, $answerChoice);
        }

        return Inertia::render('Student/Assessments/Result', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'userAnswers' => $userAnswers,
            'canShowCorrectAnswers' => $canRevealAnswers,
        ]);
    }

    /**
     * Upload a file for a QuestionType::File answer in a homework assessment.
     */
    public function uploadFileAnswer(UploadFileAnswerRequest $request, Assessment $assessment): JsonResponse
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        if (! $assessment->isHomeworkMode()) {
            return response()->json(['message' => __('messages.file_uploads_not_allowed')], 422);
        }

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        if ($this->assessmentService->isDueDatePassed($assessment)) {
            return response()->json(['message' => __('messages.assessment_due_date_passed')], 409);
        }

        $answer = $this->fileAnswerService->saveFileAnswer(
            $assignment,
            $request->integer('question_id'),
            $request->file('file')
        );

        return response()->json([
            'message' => __('messages.file_uploaded'),
            'answer' => $answer,
        ], 201);
    }

    /**
     * Delete a file answer for a QuestionType::File question in a homework assessment.
     */
    public function deleteFileAnswer(Assessment $assessment, Answer $answer): JsonResponse
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($answer->assessment_assignment_id !== $assignment->id) {
            abort(403, __('messages.do_not_own_attachment'));
        }

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        $this->fileAnswerService->deleteFileAnswer($answer);

        return response()->json(['message' => __('messages.file_deleted')]);
    }

    /**
     * Handle security violation during assessment.
     */
    public function securityViolation(SecurityViolationRequest $request, Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        if ($assessment->isHomeworkMode()) {
            return response()->json(['message' => __('messages.security_violations_not_applicable')], 422);
        }

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        if ($request->has('answers')) {
            $this->assessmentService->saveAnswers($assignment, $request->input('answers', []));
        }

        $this->assessmentService->terminateForViolation(
            $assignment,
            $assessment,
            $request->input('violation_type'),
            $request->input('violation_details')
        );

        return response()->json(['message' => __('messages.security_violation_recorded')]);
    }
}
