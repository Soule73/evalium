<?php

namespace App\Services\Core\Answer;

use App\Models\ExamAssignment;

/**
 * Interface pour le formatage des réponses d'examens
 * 
 * Cette interface définit le contrat pour formater les réponses
 * des étudiants selon différents formats (frontend, export, etc.)
 */
interface AnswerFormatterInterface
{
    /**
     * Formate les réponses d'une assignation pour l'affichage frontend
     * 
     * @param ExamAssignment $assignment L'assignation contenant les réponses
     * @return array Les réponses formatées
     */
    public function formatForFrontend(ExamAssignment $assignment): array;

    /**
     * Formate une seule réponse (question unique ou texte)
     * 
     * @param mixed $answer La réponse à formater
     * @return array La réponse formatée
     */
    public function formatSingleAnswer($answer): array;

    /**
     * Formate plusieurs réponses (questions à choix multiples)
     * 
     * @param \Illuminate\Support\Collection $answers Les réponses à formater
     * @return array Les réponses formatées
     */
    public function formatMultipleAnswers($answers): array;

    /**
     * Vérifie si une assignation a des réponses
     * 
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function hasAnswers(ExamAssignment $assignment): bool;

    /**
     * Compte le nombre de questions répondues
     * 
     * @param ExamAssignment $assignment
     * @return int
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int;

    /**
     * Récupère les statistiques de complétion
     * 
     * @param ExamAssignment $assignment
     * @return array
     */
    public function getCompletionStats(ExamAssignment $assignment): array;
}
