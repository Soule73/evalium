<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

/**
 * Validation strategy for multiple choice questions.
 *
 * Ensures that:
 * - At least 2 choices are provided
 * - At least 2 choices are marked as correct
 */
class MultipleChoiceValidationStrategy implements QuestionValidationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function validate(Validator $validator, array $question, int $index): void
    {
        // Check if choices exist and have at least 2 items
        if (! $this->hasMinimumChoices($question)) {
            $validator->errors()->add(
                "questions.{$index}.choices",
                __('validation.custom.questions.min_choices')
            );

            return;
        }

        // Check if at least 2 choices are marked as correct
        $correctCount = $this->countCorrectChoices($question);

        if ($correctCount < 2) {
            $validator->errors()->add(
                "questions.{$index}.choices",
                __('validation.custom.questions.min_correct_multiple')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'multiple';
    }

    /**
     * Check if the question has at least 2 choices.
     */
    private function hasMinimumChoices(array $question): bool
    {
        return isset($question['choices'])
            && is_array($question['choices'])
            && count($question['choices']) >= 2;
    }

    /**
     * Count the number of correct choices.
     */
    private function countCorrectChoices(array $question): int
    {
        $correctCount = 0;

        foreach ($question['choices'] as $choice) {
            if (isset($choice['is_correct']) && $choice['is_correct']) {
                $correctCount++;
            }
        }

        return $correctCount;
    }
}
