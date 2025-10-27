<?php

namespace App\Services\Core\Answer;

use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Service centralisé pour le formatage des réponses d'examens
 * 
 * Ce service élimine les duplications de logique de formatage
 * présentes dans ExamSessionService, UserAnswerService, et les contrôleurs.
 * 
 * Responsabilités :
 * - Formater les réponses pour le frontend
 * - Gérer les différents types de réponses (single, multiple, text)
 * - Calculer les statistiques de complétion
 * - Fournir des utilitaires pour la gestion des réponses
 */
class AnswerFormatter implements AnswerFormatterInterface
{
    /**
     * Formate les réponses d'une assignation pour l'affichage frontend
     * 
     * Cette méthode centralise la logique qui était dupliquée dans :
     * - UserAnswerService::formatUserAnswersForFrontend()
     * - ExamSessionService::getUserAnswers()
     * 
     * @param ExamAssignment $assignment L'assignation contenant les réponses
     * @return array Les réponses formatées, groupées par question_id
     */
    public function formatForFrontend(ExamAssignment $assignment): array
    {
        return $assignment->answers()
            ->with(['choice', 'question'])
            ->get()
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                // Une seule réponse : question à choix unique, booléenne ou texte
                if ($questionAnswers->count() === 1) {
                    return $this->formatSingleAnswer($questionAnswers->first());
                }

                // Plusieurs réponses : question à choix multiples
                return $this->formatMultipleAnswers($questionAnswers);
            })
            ->values()
            ->toArray();
    }

    /**
     * Formate une réponse unique (one_choice, boolean, text)
     * 
     * @param mixed $answer L'objet Answer
     * @return array Réponse formatée avec toutes les métadonnées
     */
    public function formatSingleAnswer($answer): array
    {
        return [
            'type' => 'single',
            'question_id' => $answer->question_id,
            'choice_id' => $answer->choice_id,
            'answer_text' => $answer->answer_text,
            'choice' => $answer->choice,
            'score' => $answer->score,
            'feedback' => $answer->feedback,
        ];
    }

    /**
     * Formate plusieurs réponses (questions à choix multiples)
     * 
     * @param Collection $answers Collection des réponses pour la même question
     * @return array Réponses formatées sous forme de tableau de choix
     */
    public function formatMultipleAnswers($answers): array
    {
        $firstAnswer = $answers->first();

        return [
            'type' => 'multiple',
            'question_id' => $firstAnswer->question_id,
            'choices' => $answers->map(function ($answer) {
                return [
                    'choice_id' => $answer->choice_id,
                    'choice' => $answer->choice,
                ];
            })->toArray(),
            'answer_text' => null,
            'score' => $firstAnswer->score,
            'feedback' => $firstAnswer->feedback,
        ];
    }

    /**
     * Vérifie si une assignation a au moins une réponse
     * 
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function hasAnswers(ExamAssignment $assignment): bool
    {
        return $assignment->answers()->exists();
    }

    /**
     * Compte le nombre de questions distinctes répondues
     * 
     * @param ExamAssignment $assignment
     * @return int
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int
    {
        return $assignment->answers()
            ->distinct('question_id')
            ->count('question_id');
    }

    /**
     * Récupère les statistiques de complétion de l'assignation
     * 
     * @param ExamAssignment $assignment
     * @return array Statistiques incluant total, répondues et pourcentage
     */
    public function getCompletionStats(ExamAssignment $assignment): array
    {
        $exam = $assignment->exam;
        $totalQuestions = $exam->questions()->count();
        $answeredQuestions = $this->countAnsweredQuestions($assignment);

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'unanswered_questions' => $totalQuestions - $answeredQuestions,
            'completion_percentage' => $totalQuestions > 0
                ? round(($answeredQuestions / $totalQuestions) * 100, 2)
                : 0,
            'is_complete' => $answeredQuestions === $totalQuestions,
        ];
    }

    /**
     * Récupère les données complètes pour l'affichage des résultats
     * 
     * Cette méthode remplace UserAnswerService::getStudentResultsData()
     * 
     * @param ExamAssignment $assignment
     * @return array Données formatées avec assignment, student, exam et réponses
     */
    public function getStudentResultsData(ExamAssignment $assignment): array
    {
        $assignment->load([
            'answers.question.choices',
            'answers.choice',
            'exam.questions.choices',
            'student'
        ]);

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $assignment->exam,
            'user_answers' => $this->formatForFrontend($assignment),
            'stats' => $this->getCompletionStats($assignment),
        ];
    }

    /**
     * Prépare les données de réponse selon le type de question
     * 
     * Cette méthode remplace ExamSessionService::prepareAnswerData()
     * 
     * @param string $questionType Type de question (multiple, one_choice, boolean, text)
     * @param array $requestData Données de la requête
     * @return array Données préparées pour insertion
     */
    public function prepareAnswerData(string $questionType, array $requestData): array
    {
        // Questions à choix (multiple, one_choice, boolean)
        if (in_array($questionType, ['multiple', 'one_choice', 'boolean'])) {
            return [
                'choice_id' => $requestData['choice_id'] ?? null,
                'answer_text' => null,
            ];
        }

        // Questions textuelles
        return [
            'answer_text' => $requestData['answer_text'] ?? '',
            'choice_id' => null,
        ];
    }

    /**
     * Récupère une collection groupée des réponses (ancienne signature)
     * 
     * @deprecated Utiliser formatForFrontend() qui retourne un array
     * @param ExamAssignment $assignment
     * @return Collection
     */
    public function getUserAnswersCollection(ExamAssignment $assignment): Collection
    {
        return $assignment->answers()
            ->with(['choice', 'question'])
            ->get()
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                if ($questionAnswers->count() === 1) {
                    return $this->formatSingleAnswer($questionAnswers->first());
                }

                return $this->formatMultipleAnswers($questionAnswers);
            });
    }
}
