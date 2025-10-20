<?php

namespace App\Services\Teacher;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use Illuminate\Support\Facades\DB;

class ExamScoringService
{
    /**
     * Sauvegarder les corrections manuelles d'un enseignant
     */
    public function saveTeacherCorrections(ExamAssignment $assignment, array $scores): array
    {
        return DB::transaction(function () use ($assignment, $scores) {
            $totalScore = 0;
            $updatedAnswers = 0;

            // Mettre à jour les scores des réponses
            foreach ($scores as $questionId => $scoreData) {
                $newScore = is_array($scoreData) ? $scoreData['score'] : $scoreData;

                // Pour les questions à choix multiples, on ne met à jour que la première réponse
                // car toutes les réponses de la même question ont le même score
                $answer = $assignment->answers()
                    ->where('question_id', $questionId)
                    ->first();

                if ($answer) {
                    // Si c'est une question à choix multiples, mettre à jour toutes les réponses
                    $updatedCount = $assignment->answers()
                        ->where('question_id', $questionId)
                        ->update(['score' => $newScore]);

                    if ($updatedCount > 0) {
                        $totalScore += $newScore;
                        $updatedAnswers++;
                    }
                }
            }

            // Calculer le score total et déterminer le statut final
            $hasTextQuestions = $assignment->exam->questions()
                ->where('type', 'text')
                ->exists();

            $finalStatus = $hasTextQuestions ? 'graded' : 'graded';

            // Mettre à jour l'assignation avec le score final
            $assignment->update([
                'score' => $totalScore,
                'status' => $finalStatus,
                'teacher_notes' => 'Correction effectuée le ' . now()->format('d/m/Y à H:i')
            ]);

            return [
                'success' => true,
                'updated_count' => $updatedAnswers,
                'total_score' => $totalScore,
                'final_status' => $finalStatus
            ];
        });
    }

    /**
     * Calculer le score automatique pour les questions QCM
     */
    public function calculateAutoScore(ExamAssignment $assignment): float
    {
        $totalScore = 0;

        $exam = $assignment->exam;
        $questions = $exam->questions()->with('choices')->get();

        foreach ($questions as $question) {
            // Ignorer les questions de type texte
            if ($question->type === 'text') {
                continue;
            }

            $score = $this->calculateQuestionScore($assignment, $question);
            $totalScore += $score;
        }

        return $totalScore;
    }

    /**
     * Calculer le score pour une question spécifique
     */
    private function calculateQuestionScore(ExamAssignment $assignment, $question): float
    {
        $answers = $assignment->answers()
            ->where('question_id', $question->id)
            ->with('choice')
            ->get();

        if ($answers->isEmpty()) {
            return 0;
        }

        switch ($question->type) {
            case 'one_choice':
            case 'boolean':
                $answer = $answers->first();
                return $answer->choice && $answer->choice->is_correct ? $question->points : 0;

            case 'multiple':
                $selectedChoices = $answers->pluck('choice')->filter();
                $correctChoices = $question->choices()->where('is_correct', true)->get();

                // Vérifier que toutes les bonnes réponses sont sélectionnées ET aucune mauvaise
                $selectedCorrect = $selectedChoices->where('is_correct', true);
                $selectedIncorrect = $selectedChoices->where('is_correct', false);

                if ($selectedCorrect->count() === $correctChoices->count() && $selectedIncorrect->isEmpty()) {
                    return $question->points;
                }
                return 0;

            case 'text':
                // Les questions texte doivent être corrigées manuellement
                return $answers->first()->score ?? 0;

            default:
                return 0;
        }
    }

    /**
     * Recalculer tous les scores automatiques pour un examen
     */
    public function recalculateExamScores(Exam $exam): array
    {
        $assignments = $exam->assignments()
            ->whereNotNull('submitted_at')
            ->get();

        $updated = 0;

        foreach ($assignments as $assignment) {
            $autoScore = $this->calculateAutoScore($assignment);

            // Mettre à jour seulement si le score a changé
            if ($assignment->auto_score !== $autoScore) {
                $assignment->update(['auto_score' => $autoScore]);
                $updated++;
            }
        }

        return [
            'total_assignments' => $assignments->count(),
            'updated_count' => $updated
        ];
    }

    /**
     * Sauvegarder une correction manuelle d'un professeur
     */
    public function saveManualCorrection($exam, $student, array $validatedData): array
    {

        // Récupérer l'assignation
        $assignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->firstOrFail();


        $updatedAnswers = 0;

        // Si on a des scores individuels par question
        if (isset($validatedData['scores'])) {
            foreach ($validatedData['scores'] as $scoreData) {
                $answer = $assignment->answers()
                    ->where('question_id', $scoreData['question_id'])
                    ->first();

                if ($answer) {
                    $answer->update([
                        'score' => $scoreData['score'],
                        'feedback' => $scoreData['feedback'] ?? null
                    ]);
                    $updatedAnswers++;
                }
            }
        }

        // Si on a un score et un feedback spécifiques pour une question
        if (isset($validatedData['question_id']) && isset($validatedData['score'])) {
            $answer = $assignment->answers()
                ->where('question_id', $validatedData['question_id'])
                ->first();

            if ($answer) {
                $answer->update([
                    'score' => $validatedData['score'],
                    'feedback' => $validatedData['feedback'] ?? null
                ]);
                $updatedAnswers++;
            }
        }

        // Recalculer le score total de l'assignation
        $totalScore = $assignment->answers()->sum('score');
        $assignment->update([
            'score' => $totalScore,
            'status' => 'graded',
            'teacher_notes' => $validatedData['teacher_notes'] ?? null
        ]);

        return [
            'success' => true,
            'assignment_id' => $assignment->id,
            'total_score' => $totalScore,
            'updated_answers' => $updatedAnswers,
            'status' => 'graded'
        ];
    }
}
