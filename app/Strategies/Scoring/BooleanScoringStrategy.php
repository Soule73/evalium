<?php

namespace App\Strategies\Scoring;

use App\Enums\QuestionType;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Scoring strategy for boolean questions (true/false).
 *
 * Supports two possible choices: True or False.
 * Awards full points if the correct choice is selected, zero otherwise.
 */
class BooleanScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = [QuestionType::Boolean];

    /**
     * Determine if the student's answer is correct.
     *
     * @param  Question  $question  The question being evaluated
     * @param  Collection  $answers  Student's answer(s)
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
        return 'True/False question. Points awarded if the correct boolean choice is selected.';
    }
}
