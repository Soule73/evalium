<?php

namespace App\Strategies\Validation\Score;

use Illuminate\Validation\Validator;

interface ScoreValidationStrategy
{
    /**
     * Validate score data based on specific rules.
     *
     * @param Validator $validator The validator instance
     * @param array $data The data to validate
     * @param array $context Additional context for validation
     * @return void
     */
    public function validate(Validator $validator, array $data, array $context = []): void;

    /**
     * Check if this strategy supports the given validation type.
     *
     * @param string $validationType The type of validation
     * @return bool
     */
    public function supports(string $validationType): bool;
}
