<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

/**
 * Context class that manages question validation strategies.
 * 
 * This class acts as a facade/factory that:
 * - Registers available validation strategies
 * - Selects the appropriate strategy for a given question type
 * - Delegates validation to the selected strategy
 */
class QuestionValidationContext
{
    /**
     * @var QuestionValidationStrategy[] Array of registered validation strategies
     */
    private array $strategies = [];

    /**
     * Create a new QuestionValidationContext instance.
     */
    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    /**
     * Register the default validation strategies.
     *
     * @return void
     */
    private function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new MultipleChoiceValidationStrategy());
        $this->registerStrategy(new SingleChoiceValidationStrategy());
        $this->registerStrategy(new TextQuestionValidationStrategy());
    }

    /**
     * Register a validation strategy.
     *
     * @param QuestionValidationStrategy $strategy
     * @return self
     */
    public function registerStrategy(QuestionValidationStrategy $strategy): self
    {
        $this->strategies[] = $strategy;
        return $this;
    }

    /**
     * Validate a question using the appropriate strategy.
     *
     * @param Validator $validator
     * @param array $question
     * @param int $index
     * @return void
     */
    public function validateQuestion(Validator $validator, array $question, int $index): void
    {
        $questionType = $question['type'] ?? '';

        $strategy = $this->findStrategy($questionType);

        if ($strategy) {
            $strategy->validate($validator, $question, $index);
        }
    }

    /**
     * Find a strategy that supports the given question type.
     *
     * @param string $questionType
     * @return QuestionValidationStrategy|null
     */
    private function findStrategy(string $questionType): ?QuestionValidationStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($questionType)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Validate all questions in the given data array.
     *
     * @param Validator $validator
     * @param array $questions
     * @return void
     */
    public function validateQuestions(Validator $validator, array $questions): void
    {
        foreach ($questions as $index => $question) {
            $this->validateQuestion($validator, $question, $index);
        }
    }
}
