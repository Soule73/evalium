<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

/**
 * Interface for question validation strategies.
 * 
 * Each question type (multiple choice, single choice, boolean, text)
 * can have its own validation strategy implementing this interface.
 */
interface QuestionValidationStrategy
{
    /**
     * Validate a question based on its type-specific rules.
     *
     * @param Validator $validator The validator instance
     * @param array $question The question data to validate
     * @param int $index The question index in the questions array
     * @return void
     */
    public function validate(Validator $validator, array $question, int $index): void;

    /**
     * Check if this strategy supports the given question type.
     *
     * @param string $questionType The type of question
     * @return bool
     */
    public function supports(string $questionType): bool;
}
