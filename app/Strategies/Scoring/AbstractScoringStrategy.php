<?php

namespace App\Strategies\Scoring;

use App\Models\Question;
use App\Contracts\Scoring\ScoringStrategyInterface;
use Illuminate\Support\Collection;

/**
 * Abstract base class for scoring strategies.
 * 
 * Provides common utility methods shared across all concrete scoring strategies.
 */
abstract class AbstractScoringStrategy implements ScoringStrategyInterface
{
    /**
     * Question type(s) supported by this strategy.
     *
     * @var array<string>
     */
    protected array $supportedTypes = [];

    /**
     * {@inheritDoc}
     */
    public function supports(string $questionType): bool
    {
        return in_array($questionType, $this->supportedTypes, true);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateScore(Question $question, Collection $answers): float
    {
        if ($answers->isEmpty()) {
            return 0.0;
        }

        return $this->isCorrect($question, $answers)
            ? (float) $question->points
            : 0.0;
    }

    /**
     * Check if answers contain a valid choice.
     *
     * @param Collection $answers
     * @return bool
     */
    protected function hasValidChoice(Collection $answers): bool
    {
        return $answers->first()?->choice !== null;
    }

    /**
     * Get all selected choice IDs from answers.
     *
     * @param Collection $answers
     * @return array<int>
     */
    protected function getSelectedChoiceIds(Collection $answers): array
    {
        return $answers->pluck('choice_id')->filter()->toArray();
    }

    /**
     * Get all correct choices for a question.
     *
     * @param Question $question
     * @return Collection
     */
    protected function getCorrectChoices(Question $question): Collection
    {
        return $question->choices()->where('is_correct', true)->get();
    }

    /**
     * Check if a specific choice is correct.
     *
     * @param Question $question
     * @param int $choiceId
     * @return bool
     */
    protected function isChoiceCorrect(Question $question, int $choiceId): bool
    {
        return $question->choices()
            ->where('id', $choiceId)
            ->where('is_correct', true)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    abstract public function isCorrect(Question $question, Collection $answers): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function getDescription(): string;
}
