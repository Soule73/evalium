<?php

namespace App\Strategies\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Scoring strategy for single-choice questions.
 *
 * Supports questions with exactly one correct answer.
 * Awards full points if the correct choice is selected, zero otherwise.
 */
class OneChoiceScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['one_choice'];

    /**
     * Determine if the student's answer is correct.
     *
     * @param  Question  $question  The question being evaluated
     * @param  Collection  $answers  Student's selected choice
     * @return bool True if the selected choice is correct
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty() || ! $this->hasValidChoice($answers)) {
            return false;
        }

        $answer = $answers->first();

        return $answer->choice !== null && $answer->choice->is_correct === true;
    }

    /**
     * Get a human-readable description of this scoring strategy.
     *
     * @return string Strategy description
     */
    public function getDescription(): string
    {
        return 'Single correct answer. Points awarded if the correct choice is selected.';
    }
}
