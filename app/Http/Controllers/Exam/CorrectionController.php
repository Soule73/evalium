<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Group;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamScoringService;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Http\Requests\Exam\UpdateScoreRequest;
use App\Http\Requests\Exam\SaveStudentReviewRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for managing exam corrections and reviews.
 * 
 * This controller handles:
 * - Displaying student review interface
 * - Saving manual corrections and teacher notes
 * - Updating individual question scores
 */
class CorrectionController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private AnswerFormatterService $answerFormatter,
        private ExamScoringService $examScoringService
    ) {}

    /**
     * Display the review of a student's exam.
     *
     * @param Exam $exam The exam instance to be reviewed.
     * @param Group $group The group to which the student belongs.
     * @param User $student The student whose exam review is to be shown.
     * @return Response The HTTP response containing the student's exam review.
     */
    public function showStudentReview(Exam $exam, Group $group, User $student): Response
    {
        $this->authorize('view', $exam);

        $data = $this->answerFormatter->getStudentReviewData($exam, $group, $student);

        return Inertia::render('Exam/StudentReview', $data);
    }

    /**
     * Save a review for a student on a specific exam.
     * 
     * Delegates to ExamScoringService to save manual corrections,
     * teacher notes, and recalculate total score.
     *
     * @param SaveStudentReviewRequest $request The validated request containing review data.
     * @param Exam $exam The exam instance being reviewed.
     * @param Group $group The group context for the review.
     * @param User $student The student for whom the review is being saved.
     * @return RedirectResponse Redirects back after saving the review.
     */
    public function saveStudentReview(SaveStudentReviewRequest $request, Exam $exam, Group $group, User $student): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $result = $this->examScoringService->saveManualCorrection($exam, $student, $request->validated());

            $message = __('messages.scores_saved', [
                'updated_answers' => $result['updated_answers'],
                'total_score' => $result['total_score']
            ]);

            return $this->redirectWithSuccess(
                'exams.review',
                $message,
                ['exam' => $exam->id, 'student' => $student->id, 'group' => $group->id]
            );
        } catch (\Exception $e) {
            Log::error('Error saving correction', [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);

            return $this->redirectWithError(
                'exams.review',
                __('messages.error_saving_correction'),
                ['exam' => $exam->id, 'student' => $student->id, 'group' => $group->id]
            );
        }
    }

    /**
     * Update the score for a specific question in a student's exam.
     * 
     * Delegates to ExamScoringService to save individual question score,
     * teacher notes, and feedback. Typically used for AJAX updates.
     *
     * @param UpdateScoreRequest $request The validated request containing score update information.
     * @param Exam $exam The exam instance to update the score for.
     * @return JsonResponse The JSON response indicating the result of the update operation.
     */
    public function updateScore(UpdateScoreRequest $request, Exam $exam): JsonResponse
    {
        try {
            $validated = $request->validated();

            $studentId = $validated['student_id'] ?? request()->route('student');
            $assignment = $exam->assignments()->where('student_id', $studentId)->firstOrFail();

            $scores = [
                $validated['question_id'] => [
                    'score' => $validated['score'],
                    'teacher_notes' => $validated['teacher_notes'] ?? null,
                    'feedback' => $validated['feedback'] ?? null
                ]
            ];

            $this->examScoringService->saveTeacherCorrections($assignment, $scores);

            return response()->json([
                'success' => true,
                'message' => __('messages.score_updated')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating score', [
                'exam_id' => $exam->id,
                'student_id' => $studentId,
                'question_id' => $validated['question_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.error_updating_score')
            ], 422);
        }
    }
}
