<?php

namespace App\Services\Shared;

use App\Models\Exam;
use App\Models\Group;
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
    public function getStudentResultsData(ExamAssignment $assignment, Exam $exam, Group $group): array
    {

        $assignment->load([
            'answers.question.choices',
            'answers.choice',
            'exam.questions.choices',
            'student'
        ]);

        $exam->load('teacher');

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $assignment->exam,
            'group' => $group,
            'creator' => $exam->teacher,
            'userAnswers' => $this->answerFormatter->formatForFrontend($assignment),
            'stats' => $this->answerFormatter->getCompletionStats($assignment),
        ];
    }

    /**
     * Récupérer les données formatées pour la page de révision/correction
     */
    public function getStudentReviewData(ExamAssignment $assignment, Exam $exam, Group $group): array
    {
        if (!$assignment->relationLoaded('answers')) {
            $assignment->load(['answers.choice']);
        }

        $loadedExam = $assignment->exam;

        $questionsById = $loadedExam->questions->keyBy('id');

        foreach ($assignment->answers as $answer) {
            if (!$answer->relationLoaded('question') && isset($questionsById[$answer->question_id])) {
                $answer->setRelation('question', $questionsById[$answer->question_id]);
            }
        }

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $loadedExam,
            'group' => $group,
            'questions' => $loadedExam->questions,
            'userAnswers' => $this->answerFormatter->formatForFrontend($assignment),
            'totalQuestions' => $loadedExam->questions->count(),
            'totalPoints' => $loadedExam->questions->sum('points'),
        ];
    }
}
