<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

/**
 * Validation strategy for single choice and boolean questions.
 * 
 * Ensures that:
 * - At least 2 choices are provided
 * - Exactly 1 choice is marked as correct
 */
class SingleChoiceValidationStrategy implements QuestionValidationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function validate(Validator $validator, array $question, int $index): void
    {
        // Check if choices exist and have at least 2 items
        if (!$this->hasMinimumChoices($question)) {
            $validator->errors()->add(
                "questions.{$index}.choices",
                __('validation.custom.questions.min_choices')
            );
            return;
        }

        // Check if exactly 1 choice is marked as correct
        $correctCount = $this->countCorrectChoices($question);

        if ($correctCount !== 1) {
            $questionType = $question['type'] ?? '';
            $questionTypeLabel = $this->getQuestionTypeLabel($questionType);

            $validator->errors()->add(
                "questions.{$index}.choices",
                __('validation.custom.questions.exactly_one_correct', ['type' => $questionTypeLabel])
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $questionType): bool
    {
        return in_array($questionType, ['one_choice', 'boolean']);
    }

    /**
     * Check if the question has at least 2 choices.
     *
     * @param array $question
     * @return bool
     */
    private function hasMinimumChoices(array $question): bool
    {
        return isset($question['choices'])
            && is_array($question['choices'])
            && count($question['choices']) >= 2;
    }

    /**
     * Count the number of correct choices.
     *
     * @param array $question
     * @return int
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

    /**
     * Get the translated label for the question type.
     *
     * @param string $questionType
     * @return string
     */
    private function getQuestionTypeLabel(string $questionType): string
    {
        return $questionType === 'one_choice'
            ? __('validation.attributes.one_choice')
            : __('validation.attributes.boolean');
    }
}
