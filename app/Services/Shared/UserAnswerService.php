<?php

namespace App\Services\Shared;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\Core\Answer\AnswerFormatter;

/**
 * Service de gestion des réponses utilisateur
 * 
 * Ce service agit comme une façade pour AnswerFormatter,
 * fournissant des méthodes de haut niveau pour les contrôleurs.
 * 
 * Note : Ce service délègue désormais toute la logique de formatage
 * au AnswerFormatter centralisé pour éviter les duplications.
 */
class UserAnswerService
{
    public function __construct(
        private readonly AnswerFormatter $answerFormatter
    ) {}

    /**
     * Organiser les réponses d'un assignment selon la structure attendue par le frontend
     * 
     * @deprecated Utiliser directement AnswerFormatter::formatForFrontend()
     */
    public function formatUserAnswersForFrontend(ExamAssignment $assignment): array
    {
        return $this->answerFormatter->formatForFrontend($assignment);
    }

    /**
     * Vérifier si une assignation a des réponses
     */
    public function assignmentHasAnswers(ExamAssignment $assignment): bool
    {
        return $this->answerFormatter->hasAnswers($assignment);
    }

    /**
     * Compter le nombre de questions répondues dans une assignation
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int
    {
        return $this->answerFormatter->countAnsweredQuestions($assignment);
    }

    /**
     * Récupérer les statistiques de réponses pour une assignation
     */
    public function getAnswerStats(ExamAssignment $assignment): array
    {
        return $this->answerFormatter->getCompletionStats($assignment);
    }

    /**
     * Récupérer les données formatées pour l'affichage des résultats d'un étudiant
     */
    public function getStudentResultsData(ExamAssignment $assignment): array
    {
        $data = $this->answerFormatter->getStudentResultsData($assignment);

        // Ajouter le creator (teacher) pour compatibilité avec les vues existantes
        $data['creator'] = $assignment->exam->teacher;
        $data['formattedAnswers'] = $data['user_answers']; // Alias pour compatibilité
        $data['answers'] = $data['user_answers']; // Alias pour compatibilité avec les tests

        return $data;
    }

    /**
     * Récupérer les données formatées pour la page de révision/correction
     */
    public function getStudentReviewData(ExamAssignment $assignment): array
    {
        $assignment->load([
            'answers.question.choices',
            'answers.choice',
            'exam.questions.choices',
            'student'
        ]);

        $exam = $assignment->exam;
        $userAnswers = $this->answerFormatter->formatForFrontend($assignment);

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $exam,
            'questions' => $exam->questions,
            'userAnswers' => $userAnswers,
            'totalQuestions' => $exam->questions->count(),
            'totalPoints' => $exam->questions->sum('points'),
        ];
    }
}
