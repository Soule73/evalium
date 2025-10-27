<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Classe abstraite de base pour les stratégies de scoring
 * 
 * Fournit des méthodes utilitaires communes à toutes les stratégies
 */
abstract class AbstractScoringStrategy implements ScoringStrategyInterface
{
    /**
     * Type(s) de question supporté(s) par cette stratégie
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
     * Vérifie si les réponses contiennent un choix valide
     *
     * @param Collection $answers
     * @return bool
     */
    protected function hasValidChoice(Collection $answers): bool
    {
        return $answers->first()?->choice !== null;
    }

    /**
     * Récupère tous les IDs de choix sélectionnés
     *
     * @param Collection $answers
     * @return array<int>
     */
    protected function getSelectedChoiceIds(Collection $answers): array
    {
        return $answers->pluck('choice_id')->filter()->toArray();
    }

    /**
     * Récupère tous les choix corrects pour une question
     *
     * @param Question $question
     * @return Collection
     */
    protected function getCorrectChoices(Question $question): Collection
    {
        return $question->choices()->where('is_correct', true)->get();
    }

    /**
     * Vérifie si un choix spécifique est correct
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
