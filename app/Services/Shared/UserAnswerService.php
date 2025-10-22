<?php

namespace App\Services\Shared;

use App\Models\Exam;
use App\Models\ExamAssignment;

class UserAnswerService
{
    /**
     * Organiser les réponses d'un assignment selon la structure attendue par le frontend
     * Cette méthode centralise la logique de structuration des données qui était dupliquée
     * entre les contrôleurs Teacher et Student
     */
    public function formatUserAnswersForFrontend(ExamAssignment $assignment): array
    {
        return $assignment->answers()
            ->with(['choice', 'question'])
            ->get()
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                if ($questionAnswers->count() === 1) {
                    $answer = $questionAnswers->first();
                    return [
                        'type' => 'single',
                        'choice_id' => $answer->choice_id,
                        'answer_text' => $answer->answer_text,
                        'choice' => $answer->choice,
                        'score' => $answer->score,
                        'feedback' => $answer->feedback,
                        'question_id' => $answer->question_id,
                    ];
                }

                $firstAnswer = $questionAnswers->first();
                return [
                    'type' => 'multiple',
                    'choices' => $questionAnswers->map(function ($answer) {
                        return [
                            'choice_id' => $answer->choice_id,
                            'choice' => $answer->choice,
                        ];
                    })->toArray(),
                    'answer_text' => null,
                    'score' => $firstAnswer->score,
                    'feedback' => $firstAnswer->feedback,
                    'question_id' => $firstAnswer->question_id,
                ];
            })->values()->toArray();
    }

    /**
     * Vérifier si une assignation a des réponses
     */
    public function assignmentHasAnswers(ExamAssignment $assignment): bool
    {
        return $assignment->answers()->exists();
    }

    /**
     * Compter le nombre de questions répondues dans une assignation
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int
    {
        return $assignment->answers()
            ->distinct('question_id')
            ->count('question_id');
    }

    /**
     * Récupérer les statistiques de réponses pour une assignation
     */
    public function getAnswerStats(ExamAssignment $assignment): array
    {
        $exam = $assignment->exam;
        $totalQuestions = $exam->questions()->count();
        $answeredQuestions = $this->countAnsweredQuestions($assignment);

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'completion_percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0
        ];
    }

    /**
     * Récupérer les données formatées pour l'affichage des résultats d'un étudiant
     */
    public function getStudentResultsData(ExamAssignment $assignment): array
    {
        $assignment->load(['answers.question.choices', 'answers.choice', 'exam.questions.choices', 'student']);
        $exam = $assignment->exam;
        $student = $assignment->student;

        $userAnswers = $this->formatUserAnswersForFrontend($assignment);

        return [
            'assignment' => $assignment,
            'student' => $student,
            'exam' => $exam,
            'formattedAnswers' => $userAnswers,
            'userAnswers' => $userAnswers,
            'creator' => $exam->teacher
        ];
    }

    /**
     * Récupérer les données formatées pour la page de révision/correction
     */
    public function getStudentReviewData(ExamAssignment $assignment): array
    {

        $assignment->load(['answers.question.choices', 'answers.choice', 'exam.questions.choices', 'student']);
        $exam = $assignment->exam;
        $student = $assignment->student;


        $userAnswers = $this->formatUserAnswersForFrontend($assignment);

        return [
            'assignment' => $assignment,
            'student' => $student,
            'exam' => $exam,
            'questions' => $exam->questions,
            'userAnswers' => $userAnswers,
            'totalQuestions' => $exam->questions->count(),
            'totalPoints' => $exam->questions->sum('points')
        ];
    }
}
