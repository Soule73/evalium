<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Stratégie de scoring pour les questions à choix unique (one_choice)
 * 
 * Une seule réponse correcte possible.
 * Score = points de la question si le choix sélectionné est correct, 0 sinon.
 */
class OneChoiceScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['one_choice'];

    /**
     * {@inheritDoc}
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty() || !$this->hasValidChoice($answers)) {
            return false;
        }

        $answer = $answers->first();

        // Vérifier que le choix existe et est correct
        return $answer->choice !== null && $answer->choice->is_correct === true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Une seule réponse correcte. Le score est attribué si le bon choix est sélectionné.';
    }
}
