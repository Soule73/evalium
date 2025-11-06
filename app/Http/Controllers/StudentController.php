<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use App\Repositories\AssignmentRepository;
use App\Services\Core\ExamQueryService;
use App\Services\Student\ExamSessionService;
use App\Services\Student\StudentExamAccessService;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Services\Core\Scoring\ScoringService;
use App\Http\Requests\Student\SubmitExamRequest;
use App\Http\Requests\Student\SaveAnswersRequest;
use App\Http\Requests\Student\SecurityViolationRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly ExamQueryService $examQueryService,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly ExamSessionService $examSessionService,
        private readonly ScoringService $scoringService,
        private readonly AnswerFormatterService $answerFormatter,
        private readonly StudentExamAccessService $accessService
    ) {}

    /**
     * Display a listing of student's groups with exam statistics.
     * 
     * Delegates to ExamQueryService to load groups with exam counts,
     * completion stats, and pending exams.
     *
     * @param Request $request The HTTP request instance.
     * @return InertiaResponse The HTTP response containing the groups data.
     */
    public function index(Request $request): InertiaResponse
    {
        $student = $request->user();

        $perPage = $request->input('per_page', 15);

        $groups = $this->examQueryService->getStudentGroupsWithStats($student, $perPage);

        return Inertia::render('Student/Groups/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Display exams for a specific group.
     * 
     * Verifies student membership in the group and delegates to
     * ExamQueryService to load paginated exams with filtering.
     *
     * @param \App\Models\Group $group
     * @param Request $request The HTTP request instance.
     * @return InertiaResponse The HTTP response containing the group exams data.
     */
    public function showGroup(\App\Models\Group $group, Request $request): InertiaResponse
    {
        $student = $request->user();

        $this->accessService->loadGroupLevel($group);

        $studentPivot = $this->accessService->getStudentGroupMembership($group, $student);

        if (!$studentPivot) {
            abort(403, __('messages.not_member_of_group'));
        }

        $isActiveGroup = $this->accessService->isActiveGroupMembership($studentPivot);

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status') ?: null;
        $search = $request->input('search');

        $pagination = $this->examQueryService->getExamsForStudentInGroup(
            $group,
            $student,
            $perPage,
            $status,
            $search,
            $isActiveGroup
        );

        return Inertia::render('Student/Groups/Show', [
            'group' => $group,
            'pagination' => $pagination,
            'isActiveGroup' => $isActiveGroup,
        ]);
    }

    /**
     * Display the specified exam details for the student.
     * 
     * Shows different views based on exam completion status:
     * - If can take: Show exam overview with start button
     * - If completed: Show results with answers and score
     *
     * @param Exam $exam The exam instance to be displayed.
     * @param Request $request The HTTP request instance.
     * @return InertiaResponse The HTTP response containing the exam details.
     */
    public function show(Exam $exam, Request $request): InertiaResponse
    {
        $student = $request->user();

        $assignment = $this->assignmentRepository->findByExamAndStudent($exam, $student);

        if (!$assignment) {
            abort(403, __('messages.exam_not_assigned'));
        }

        $canTake = $assignment->submitted_at === null;

        $this->accessService->loadExamTeacher($exam);

        $group = $this->accessService->getStudentGroupForExam($exam, $student);

        if ($canTake) {
            $questionsCount = $this->accessService->getQuestionsCount($exam);

            return Inertia::render('Student/Exams/Show', [
                'exam' => $exam,
                'assignment' => $assignment,
                'canTake' => $canTake,
                'questionsCount' => $questionsCount,
                'creator' => $exam->teacher,
                'group' => $group,
            ]);
        }

        $this->accessService->loadExamQuestionsWithChoices($exam);
        $this->accessService->loadAssignmentAnswers($assignment);
        $userAnswers = $this->answerFormatter->formatForFrontend($assignment);

        return Inertia::render('Student/Exams/Results', [
            'exam' => $exam,
            'assignment' => $assignment,
            'userAnswers' => $userAnswers,
            'creator' => $exam->teacher,
            'group' => $group,
        ]);
    }


    /**
     * Display the exam interface for the student to take.
     * 
     * Validates exam availability, timing, and assignment status.
     * Delegates to ExamSessionService to start the exam session.
     *
     * @param Exam $exam The exam instance to be taken.
     * @param Request $request The HTTP request instance.
     * @return InertiaResponse|RedirectResponse The HTTP response containing the exam interface or a redirect on error.
     */
    public function take(Exam $exam, Request $request): InertiaResponse|RedirectResponse
    {
        $this->authorize('view', $exam);

        $student = $request->user();

        $existingAssignment = $this->assignmentRepository->findByExamAndStudent($exam, $student);

        if (!$existingAssignment) {
            return $this->redirectWithError('student.exams.index', __('messages.exam_not_assigned'), []);
        }

        $assignment = $existingAssignment->exists
            ? $existingAssignment
            : $this->examSessionService->findOrCreateAssignment($exam, $student);

        $this->authorize('canTake', $assignment);

        $this->examSessionService->startExam($assignment);

        $this->accessService->loadExamQuestionsWithChoices($exam);

        $userAnswers = $this->answerFormatter->formatForFrontend($assignment);

        $group = $this->accessService->getStudentGroupForExam($exam, $student);

        return Inertia::render('Student/Exams/Take', [
            'exam' => $exam,
            'assignment' => $assignment,
            'questions' => $exam->questions,
            'userAnswers' => $userAnswers,
            'group' => $group,
        ]);
    }

    /**
     * Save student answers during exam (AJAX endpoint).
     * 
     * Delegates to ExamSessionService to persist answers without
     * submitting the exam. Used for auto-save functionality.
     *
     * @param SaveAnswersRequest $request The validated request containing answers.
     * @param Exam $exam The exam instance.
     * @return JsonResponse The JSON response indicating success or failure.
     */
    public function saveAnswers(SaveAnswersRequest $request, Exam $exam): JsonResponse
    {

        $student = Auth::user();

        $assignment = $this->assignmentRepository->findStartedAssignment($exam, $student->id);

        try {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $request->validated()['answers']);

            return response()->json([
                'success' => true,
                'message' => __('messages.answers_saved')
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving student answers', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.error_saving_answers')
            ], 500);
        }
    }

    /**
     * Handle security violation during exam (AJAX endpoint).
     * 
     * Saves current answers, calculates score, and force-submits exam
     * with violation flag. No authorization check - violations must be
     * processed regardless of exam accessibility.
     *
     * @param SecurityViolationRequest $request
     * @param Exam $exam
     * @return JsonResponse
     */
    public function handleSecurityViolation(SecurityViolationRequest $request, Exam $exam): JsonResponse
    {
        $student = Auth::user();

        $assignment = $this->assignmentRepository->findStartedAssignment($exam, $student->id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => __('messages.exam_not_found_or_submitted')
            ], 404);
        }

        $validated = $request->validated();

        if (isset($validated['answers'])) {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $validated['answers']);
        }

        $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

        $this->examSessionService->submitExam(
            $assignment,
            $autoScore,
            false,
            true,
            $validated['violation_type']
        );

        $assignment->refresh();

        return response()->json([
            'success' => true,
            'message' => __('messages.security_violation_processed'),
            'exam_terminated' => true,
            'violation_type' => $validated['violation_type'],
            'violation_details' => $validated['violation_details'] ?? '',
            'assignment' => $assignment
        ]);
    }


    /**
     * Abandon an exam without scoring.
     * 
     * Delegates to ExamSessionService to submit exam with
     * abandoned flag (no score calculation).
     *
     * @param Exam $exam
     * @return Response
     */
    public function abandon(Exam $exam): Response
    {
        $student = Auth::user();

        $this->authorize('view', $exam);

        $assignment = $this->assignmentRepository->findStartedAssignment($exam, $student->id);

        if (!$assignment) {
            abort(404, __('messages.exam_not_found_or_submitted'));
        }

        $this->examSessionService->submitExam($assignment, false, true);

        return response('', 200);
    }


    /**
     * Submit exam with answers.
     * 
     * Saves final answers, calculates auto-correctable score,
     * and delegates to ExamSessionService to finalize submission.
     * Marks exam as requiring manual correction if text questions present.
     *
     * @param SubmitExamRequest $request
     * @param Exam $exam
     * @return RedirectResponse
     */
    public function submit(SubmitExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        $student = Auth::user();

        $assignment = $this->assignmentRepository->findStartedAssignment($exam, $student->id);

        if (!$assignment) {
            return back()->withErrors(['exam' => __('messages.exam_must_start_before_submit')]);
        }

        $validated = $request->validated();

        if (isset($validated['answers'])) {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $validated['answers']);
        }

        $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

        $hasTextQuestions = $this->accessService->hasTextQuestions($exam);

        $isSecurityViolation = $validated['security_violation'] ?? false;

        $this->examSessionService->submitExam($assignment, $autoScore, $hasTextQuestions, $isSecurityViolation);

        return $this->redirectWithSuccess('student.exams.show', __('messages.exam_submitted'), ['exam' => $exam->id]);
    }
}
