<?php

namespace App\Strategies\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Scoring strategy for text-based questions (text, essay).
 * 
 * These questions require manual grading by a teacher.
 * The score is retrieved from the answer after teacher evaluation.
 */
class TextQuestionScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['text', 'essay'];

    /**
     * Calculate the score for a text-based answer.
     * 
     * Returns the manually assigned score from the teacher's evaluation.
     * Returns zero if not yet graded.
     * 
     * @param Question $question The question being evaluated
     * @param Collection $answers Student's text answer
     * @return float The assigned score or 0.0 if not yet graded
     */
    public function calculateScore(Question $question, Collection $answers): float
    {
        if ($answers->isEmpty()) {
            return 0.0;
        }

        $answer = $answers->first();

        return $answer->score ?? 0.0;
    }

    /**
     * Determine if the answer has been graded with a positive score.
     * 
     * Since text questions cannot be auto-graded, this checks if a teacher
     * has assigned a score greater than zero.
     * 
     * @param Question $question The question being evaluated
     * @param Collection $answers Student's text answer
     * @return bool True if graded with a score > 0
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty()) {
            return false;
        }

        $answer = $answers->first();
        return isset($answer->score) && $answer->score > 0;
    }

    /**
     * Get a human-readable description of this scoring strategy.
     * 
     * @return string Strategy description
     */
    public function getDescription(): string
    {
        return 'Text-based question requiring manual grading. Score is assigned by the teacher.';
    }
}
