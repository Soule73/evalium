<?php

namespace App\Contracts\Scoring;

use App\Enums\QuestionType;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Interface for question scoring strategies.
 *
 * Each question type (multiple choice, one choice, text, boolean)
 * has its own implementation of this interface.
 */
interface ScoringStrategyInterface
{
    /**
     * Check if this strategy can handle the given question type.
     *
     * @param  QuestionType  $questionType  The question type enum value
     * @return bool True if this strategy supports the question type
     */
    public function supports(QuestionType $questionType): bool;

    /**
     * Calculate the score for a specific question.
     *
     * @param  Question  $question  The question to evaluate
     * @param  Collection  $answers  The student's answers for this question
     * @return float The earned score (0 if incorrect, question points if correct)
     */
    public function calculateScore(Question $question, Collection $answers): float;

    /**
     * Determine if an answer is correct.
     *
     * @param  Question  $question  The question being evaluated
     * @param  Collection  $answers  The provided answers
     * @return bool True if the answer is correct
     */
    public function isCorrect(Question $question, Collection $answers): bool;

    /**
     * Get a description of the scoring logic for this strategy.
     *
     * @return string Human-readable description of the scoring approach
     */
    public function getDescription(): string;
}
