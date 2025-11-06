<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

/**
 * Validation strategy for text questions.
 * 
 * Text questions don't require choices, so this is a no-op strategy.
 * It's included for completeness and to make the system extensible.
 */
class TextQuestionValidationStrategy implements QuestionValidationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function validate(Validator $validator, array $question, int $index): void
    {
        // Text questions don't have choices, so no additional validation needed
        // This method is intentionally empty but can be extended in the future
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'text';
    }
}
