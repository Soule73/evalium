<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\Shared\UserAnswerService;
use App\Services\Exam\ExamScoringService;
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
        private UserAnswerService $userAnswerService,
        private ExamScoringService $examScoringService
    ) {}

    /**
     * Display the review of a student's exam.
     *
     * @param Exam $exam The exam instance to be reviewed.
     * @param User $student The student whose exam review is to be shown.
     * @return Response The HTTP response containing the student's exam review.
     */
    public function showStudentReview(Exam $exam, User $student): Response
    {
        $this->authorize('view', $exam);

        $assignment = $exam->assignments()->where('student_id', $student->id)->firstOrFail();

        $data = $this->userAnswerService->getStudentReviewData($assignment);

        return Inertia::render('Exam/StudentReview', $data);
    }

    /**
     * Saves a review for a student on a specific exam.
     *
     * @param SaveStudentReviewRequest $request The validated request containing review data.
     * @param Exam $exam The exam instance being reviewed.
     * @param User $student The student for whom the review is being saved.
     * @return RedirectResponse Redirects back after saving the review.
     */
    public function saveStudentReview(SaveStudentReviewRequest $request, Exam $exam, User $student): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $result = $this->examScoringService->saveManualCorrection($exam, $student, $request->validated());

            return $this->redirectWithSuccess(
                'teacher.exams.review',
                "Correction sauvegardée avec succès ! {$result['updated_answers']} réponses mises à jour. Note total: {$result['total_score']} points.",
                ['exam' => $exam->id, 'student' => $student->id]
            );
        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde de la correction : " . $e->getMessage());

            return $this->redirectWithError(
                'teacher.exams.review',
                'Erreur lors de la sauvegarde de la correction',
                ['exam' => $exam->id, 'student' => $student->id]
            );
        }
    }

    /**
     * Updates the score for a given exam based on the provided request data.
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

            // Préparer les données au format attendu par saveTeacherCorrections
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
                'message' => 'Note mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur updateScore: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la note'
            ], 422);
        }
    }
}
