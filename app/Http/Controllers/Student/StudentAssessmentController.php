<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UploadAttachmentRequest;
use App\Models\Assessment;
use App\Models\AssignmentAttachment;
use App\Services\Student\AttachmentService;
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
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * Display a listing of student's assessments.
     */
    public function index(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $filters = $request->only(['status', 'search']);
        $perPage = $request->input('per_page', 15);

        $assignments = $this->assessmentService->getStudentAssessmentsForIndex(
            $student,
            $selectedYearId,
            $filters,
            $perPage
        );

        return Inertia::render('Student/Assessments/Index', [
            'assignments' => $assignments,
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
            'classSubject.class',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        return Inertia::render('Student/Assessments/Show', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'availability' => $assessment->getAvailabilityStatus(),
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
            return back()->with('error', __('messages.'.$availability['reason']));
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
                ? 'student.assessments.results'
                : 'student.assessments.show';

            return redirect()->route($redirectRoute, $assessment);
        }

        if ($this->assessmentService->autoSubmitIfExpired($assignment, $assessment)) {
            return redirect()
                ->route('student.assessments.results', $assessment)
                ->with('error', __('messages.assessment_time_expired'));
        }

        $availability = $assessment->getAvailabilityStatus();

        if (! $availability['available']) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->with('error', __('messages.'.$availability['reason']));
        }

        $assignment->load([
            'assessment.classSubject.class',
            'assessment.classSubject.subject',
            'assessment.questions.choices',
            'answers',
        ]);

        $remainingSeconds = $this->assessmentService->calculateRemainingSeconds($assignment, $assessment);

        $page = $assessment->isHomeworkMode()
            ? 'Student/Assessments/Work'
            : 'Student/Assessments/Take';

        $props = [
            'assignment' => $assignment,
            'assessment' => $assessment->load(['questions.choices']),
            'questions' => $assessment->questions,
            'userAnswers' => $assignment->answers,
            'remainingSeconds' => $remainingSeconds,
        ];

        if ($assessment->isHomeworkMode()) {
            $props['attachments'] = $this->attachmentService->getAttachments($assignment);
        }

        return Inertia::render($page, $props);
    }

    /**
     * Save answers (auto-save during assessment).
     */
    public function saveAnswers(Request $request, Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

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
    public function submit(Request $request, Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return back()->with('error', __('messages.assessment_already_submitted'));
        }

        if ($this->assessmentService->isTimeExpired($assignment, $assessment)) {
            $this->assessmentService->autoSubmitIfExpired($assignment, $assessment);

            return redirect()
                ->route('student.assessments.show', $assessment)
                ->with('error', __('messages.assessment_time_expired'));
        }

        if ($this->assessmentService->isDueDatePassed($assessment)) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->with('error', __('messages.assessment_due_date_passed'));
        }

        $this->assessmentService->submitAssessment($assignment, $assessment, $request->input('answers', []));

        return redirect()
            ->route('student.assessments.results', $assessment)
            ->with('success', __('messages.assessment_submitted'));
    }

    /**
     * Display assessment results.
     */
    public function results(Assessment $assessment): Response|RedirectResponse
    {
        $student = Auth::user();

        $assessment->load([
            'classSubject.class',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $assignment = $this->assessmentService->getAssignmentForResults($student, $assessment);

        if (! $assignment || ! $assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        $userAnswers = $this->assessmentService->formatUserAnswers($assignment->answers);

        return Inertia::render('Student/Assessments/Results', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'userAnswers' => $userAnswers,
        ]);
    }

    /**
     * Upload a file attachment for a homework assessment.
     */
    public function uploadAttachment(UploadAttachmentRequest $request, Assessment $assessment): JsonResponse
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

        if (! $assessment->hasFileUploads()) {
            return response()->json(['message' => __('messages.file_uploads_not_allowed')], 422);
        }

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        if ($this->assessmentService->isDueDatePassed($assessment)) {
            return response()->json(['message' => __('messages.assessment_due_date_passed')], 409);
        }

        if ($this->attachmentService->hasReachedFileLimit($assignment, $assessment)) {
            return response()->json(['message' => __('messages.file_upload_limit_reached')], 422);
        }

        $attachment = $this->attachmentService->uploadAttachment($assignment, $assessment, $request->file('file'));

        return response()->json([
            'message' => __('messages.file_uploaded'),
            'attachment' => $attachment,
        ], 201);
    }

    /**
     * Delete a file attachment from a homework assessment.
     */
    public function deleteAttachment(Assessment $assessment, AssignmentAttachment $attachment): JsonResponse
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            __('messages.cannot_access_assessment')
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($attachment->assessment_assignment_id !== $assignment->id) {
            abort(403, __('messages.do_not_own_attachment'));
        }

        if ($assignment->submitted_at) {
            return response()->json(['message' => __('messages.assessment_already_submitted')], 400);
        }

        $this->attachmentService->deleteAttachment($attachment);

        return response()->json(['message' => __('messages.file_deleted')]);
    }

    /**
     * Handle security violation during assessment.
     */
    public function securityViolation(Request $request, Assessment $assessment)
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

        $request->validate([
            'violation_type' => ['required', 'string'],
            'violation_details' => ['nullable', 'string'],
            'answers' => ['nullable', 'array'],
        ]);

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
