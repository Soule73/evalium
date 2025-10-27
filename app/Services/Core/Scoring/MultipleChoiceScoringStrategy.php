<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Stratégie de scoring pour les questions à choix multiples (multiple)
 * 
 * Plusieurs réponses correctes possibles.
 * Score = points de la question UNIQUEMENT si TOUTES les bonnes réponses sont sélectionnées
 * ET aucune mauvaise réponse n'est sélectionnée. Sinon 0.
 */
class MultipleChoiceScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = ['multiple'];

    /**
     * {@inheritDoc}
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty()) {
            return false;
        }

        // Récupérer tous les choix corrects pour la question
        $correctChoices = $this->getCorrectChoices($question);
        $correctChoiceIds = $correctChoices->pluck('id')->sort()->values()->toArray();

        // Récupérer les choix sélectionnés par l'étudiant
        $selectedChoiceIds = $this->getSelectedChoiceIds($answers);
        sort($selectedChoiceIds);

        // Vérifier que tous les choix sélectionnés existent et charger leurs données
        $selectedChoices = $answers->pluck('choice')->filter();

        // Vérifier qu'aucun choix incorrect n'a été sélectionné
        $hasIncorrectChoice = $selectedChoices->contains(function ($choice) {
            return $choice->is_correct === false;
        });

        if ($hasIncorrectChoice) {
            return false;
        }

        // Vérifier que tous les choix corrects ont été sélectionnés
        return $correctChoiceIds === $selectedChoiceIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Plusieurs réponses correctes. Le score est attribué uniquement si toutes les bonnes réponses sont sélectionnées et aucune mauvaise.';
    }
}
