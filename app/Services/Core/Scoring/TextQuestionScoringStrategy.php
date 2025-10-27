<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Stratégie de scoring pour les questions textuelles (text, essay)
 * 
 * Ces questions nécessitent une correction manuelle par un enseignant.
 * Le score est récupéré depuis la réponse (déjà évaluée) ou 0 par défaut.
 */
class TextQuestionScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['text', 'essay'];

    /**
     * {@inheritDoc}
     * 
     * Pour les questions textuelles, on ne peut pas déterminer automatiquement
     * si la réponse est correcte. On retourne le score déjà attribué par l'enseignant.
     */
    public function calculateScore(Question $question, Collection $answers): float
    {
        if ($answers->isEmpty()) {
            return 0.0;
        }

        $answer = $answers->first();

        // Retourner le score déjà attribué par l'enseignant, ou 0 si pas encore corrigé
        return $answer->score ?? 0.0;
    }

    /**
     * {@inheritDoc}
     * 
     * Les questions textuelles ne peuvent pas être évaluées automatiquement
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        // Impossible de déterminer automatiquement
        // On considère comme "correct" si un score a été attribué et qu'il est > 0
        if ($answers->isEmpty()) {
            return false;
        }

        $answer = $answers->first();
        return isset($answer->score) && $answer->score > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Question textuelle nécessitant une correction manuelle. Le score est attribué par l\'enseignant.';
    }
}
