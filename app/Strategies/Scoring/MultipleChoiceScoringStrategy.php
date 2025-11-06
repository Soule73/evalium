<?php

namespace App\Strategies\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Scoring strategy for multiple-choice questions with multiple correct answers.
 *
 * Implements an all-or-nothing scoring approach:
 * - Full points awarded only if ALL correct choices are selected
 * - AND no incorrect choices are selected
 * - Zero points otherwise
 */
class MultipleChoiceScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['multiple'];

    /**
     * Determine if the student's answers are completely correct.
     *
     * Validates that the student selected exactly all correct choices
     * and no incorrect choices.
     *
     * @param  Question  $question  The question being evaluated
     * @param  Collection  $answers  Student's selected choices
     * @return bool True if all correct choices selected and no incorrect ones
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty()) {
            return false;
        }

        $selectedChoiceIds = $this->getSelectedChoiceIds($answers);

        if (empty($selectedChoiceIds)) {
            return false;
        }

        $correctChoices = $this->getCorrectChoices($question);
        $correctChoiceIds = $correctChoices->pluck('id')->toArray();

        return count($selectedChoiceIds) === count($correctChoiceIds)
            && empty(array_diff($selectedChoiceIds, $correctChoiceIds))
            && empty(array_diff($correctChoiceIds, $selectedChoiceIds));
    }

    /**
     * Get a human-readable description of this scoring strategy.
     *
     * @return string Strategy description
     */
    public function getDescription(): string
    {
        return 'Multiple correct answers. Points awarded only if all correct choices are selected and no incorrect choices.';
    }
}
