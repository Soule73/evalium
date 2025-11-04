<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Facades\DB;
use App\Services\Core\Scoring\ScoringService;

/**
 * Service for managing exam scoring and teacher corrections.
 * 
 * Handles manual grading, score recalculation, and assignment status updates.
 */
class ExamScoringService
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Save manual corrections from a teacher.
     * 
     * Updates answer scores and feedback for multiple questions in a transaction.
     * Automatically updates assignment status to 'graded' after corrections.
     *
     * @param ExamAssignment $assignment Assignment to correct
     * @param array $scores Question scores and feedback [questionId => ['score' => float, 'feedback' => ?string]]
     * @return array Summary with total score and updated count
     */
    public function saveTeacherCorrections(ExamAssignment $assignment, array $scores): array
    {
        return DB::transaction(function () use ($assignment, $scores) {
            $totalScore = 0;
            $updatedAnswers = 0;

            foreach ($scores as $questionId => $scoreData) {
                $newScore = is_array($scoreData) ? $scoreData['score'] : $scoreData;
                $feedback = is_array($scoreData) ? ($scoreData['feedback'] ?? $scoreData['teacher_notes'] ?? null) : null;

                $answer = $assignment->answers()
                    ->where('question_id', $questionId)
                    ->first();

                if ($answer) {
                    $updateData = ['score' => $newScore];

                    if ($feedback !== null) {
                        $updateData['feedback'] = $feedback;
                    }

                    $updatedCount = $assignment->answers()
                        ->where('question_id', $questionId)
                        ->update($updateData);

                    if ($updatedCount > 0) {
                        $totalScore += $newScore;
                        $updatedAnswers++;
                    }
                }
            }

            $hasTextQuestions = $assignment->exam->questions()
                ->where('type', 'text')
                ->exists();

            $finalStatus = $hasTextQuestions ? 'graded' : 'graded';

            $assignment->update([
                'score' => $totalScore,
                'status' => $finalStatus,
            ]);

            return [
                'success' => true,
                'updated_count' => $updatedAnswers,
                'total_score' => $totalScore,
                'final_status' => $finalStatus
            ];
        });
    }

    /**
     * Recalculate all automatic scores for an exam.
     * 
     * Useful after exam questions or scoring rules are modified.
     * Only processes submitted assignments.
     *
     * @param Exam $exam The exam to recalculate scores for
     * @return array Statistics with total and updated assignment counts
     */
    public function recalculateExamScores(Exam $exam): array
    {
        $assignments = $exam->assignments()
            ->whereNotNull('submitted_at')
            ->get();

        $updated = 0;

        foreach ($assignments as $assignment) {
            $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

            if ($assignment->auto_score !== $autoScore) {
                $assignment->update(['auto_score' => $autoScore]);
                $updated++;
            }
        }

        return [
            'total_assignments' => $assignments->count(),
            'updated_count' => $updated
        ];
    }

    /**
     * Save manual correction from a teacher.
     * 
     * Optimized to use bulk operations instead of N+1 queries.
     * Supports two input formats:
     * - Batch: ['scores' => [['question_id' => X, 'score' => Y, 'feedback' => Z]]]
     * - Single: ['question_id' => X, 'score' => Y, 'feedback' => Z]
     *
     * @param Exam $exam Exam being corrected
     * @param \App\Models\User $student Student whose work is corrected
     * @param array $validatedData Scores and feedback data
     * @return array Summary with updated count and scores
     */
    public function saveManualCorrection($exam, $student, array $validatedData): array
    {
        $assignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->firstOrFail();

        $updatedAnswers = 0;

        if (isset($validatedData['scores'])) {
            $answers = $assignment->answers()->get()->keyBy('question_id');

            foreach ($validatedData['scores'] as $scoreData) {
                $answer = $answers->get($scoreData['question_id']);

                if ($answer) {
                    $answer->update([
                        'score' => $scoreData['score'],
                        'feedback' => $scoreData['feedback'] ?? null
                    ]);
                    $updatedAnswers++;
                }
            }
        }

        if (isset($validatedData['question_id']) && isset($validatedData['score'])) {
            $answer = $assignment->answers()
                ->where('question_id', $validatedData['question_id'])
                ->first();

            if ($answer) {
                $answer->update([
                    'score' => $validatedData['score'],
                    'feedback' => $validatedData['feedback'] ?? $validatedData['teacher_notes'] ?? null
                ]);
                $updatedAnswers++;
            }
        }

        $totalScore = $assignment->answers()->sum('score');
        $assignment->update([
            'score' => $totalScore,
            'status' => 'graded',
            'teacher_notes' => $validatedData['teacher_notes'] ?? null
        ]);

        return [
            'success' => true,
            'assignment_id' => $assignment->id,
            'total_score' => $totalScore,
            'updated_answers' => $updatedAnswers,
            'status' => 'graded'
        ];
    }
}
