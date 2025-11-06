<?php

namespace App\Strategies\Validation\Score;

use Illuminate\Validation\Validator;

class ScoreValidationContext
{
    /**
     * @var ScoreValidationStrategy[]
     */
    private array $strategies = [];

    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    private function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new QuestionExistsInExamValidationStrategy);
        $this->registerStrategy(new ScoreNotExceedsMaxValidationStrategy);
        $this->registerStrategy(new SingleQuestionExistsValidationStrategy);
        $this->registerStrategy(new StudentAssignmentValidationStrategy);
    }

    public function registerStrategy(ScoreValidationStrategy $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    public function validate(Validator $validator, array $data, array $validationTypes, array $context = []): void
    {
        foreach ($validationTypes as $validationType) {
            $strategy = $this->findStrategy($validationType);

            if ($strategy) {
                $strategy->validate($validator, $data, $context);
            }
        }
    }

    private function findStrategy(string $validationType): ?ScoreValidationStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($validationType)) {
                return $strategy;
            }
        }

        return null;
    }
}
