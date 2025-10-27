<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Interface pour les stratégies de calcul de score
 * 
 * Chaque type de question (multiple choice, one choice, text, boolean)
 * aura sa propre implémentation de cette interface.
 */
interface ScoringStrategyInterface
{
    /**
     * Vérifie si cette stratégie peut gérer le type de question donné
     *
     * @param string $questionType Le type de question (multiple, one_choice, boolean, text, essay)
     * @return bool
     */
    public function supports(string $questionType): bool;

    /**
     * Calcule le score pour une question spécifique
     *
     * @param Question $question La question à évaluer
     * @param Collection $answers Les réponses de l'étudiant pour cette question
     * @return float Le score obtenu (0 si incorrect, points de la question si correct)
     */
    public function calculateScore(Question $question, Collection $answers): float;

    /**
     * Vérifie si une réponse est correcte
     *
     * @param Question $question La question
     * @param Collection $answers Les réponses données
     * @return bool
     */
    public function isCorrect(Question $question, Collection $answers): bool;

    /**
     * Retourne une explication du scoring pour cette stratégie
     *
     * @return string
     */
    public function getDescription(): string;
}
