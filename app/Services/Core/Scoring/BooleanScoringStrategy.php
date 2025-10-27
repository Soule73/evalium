<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Stratégie de scoring pour les questions booléennes (boolean)
 * 
 * Deux choix possibles : Vrai ou Faux.
 * Score = points de la question si le bon choix est sélectionné, 0 sinon.
 */
class BooleanScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['boolean'];

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
        return 'Question Vrai/Faux. Le score est attribué si le bon choix booléen est sélectionné.';
    }
}
