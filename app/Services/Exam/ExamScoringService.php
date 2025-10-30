<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Facades\DB;
use App\Services\Core\Scoring\ScoringService;

/**
 * Service pour gérer la notation des examens
 * 
 * @package App\Services\Exam
 */
class ExamScoringService
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Sauvegarder les corrections manuelles d'un enseignant
     */
    public function saveTeacherCorrections(ExamAssignment $assignment, array $scores): array
    {
        return DB::transaction(function () use ($assignment, $scores) {
            $totalScore = 0;
            $updatedAnswers = 0;

            foreach ($scores as $questionId => $scoreData) {
                $newScore = is_array($scoreData) ? $scoreData['score'] : $scoreData;
                $feedback = is_array($scoreData) ? ($scoreData['feedback'] ?? $scoreData['teacher_notes'] ?? null) : null;

                // Pour les questions à choix multiples, on ne met à jour que la première réponse
                // car toutes les réponses de la même question ont le même score
                $answer = $assignment->answers()
                    ->where('question_id', $questionId)
                    ->first();

                if ($answer) {
                    // Si c'est une question à choix multiples, mettre à jour toutes les réponses
                    $updateData = ['score' => $newScore];

                    if ($feedback !== null) {
                        $updateData['feedback'] = $feedback;
                    }

                    $updatedCount = $assignment->answers()
                        ->where('question_id', $questionId)
                        ->update($updateData);

                    if ($updatedCount > 0) {
                        $totalScore += $newScore;
                        $updatedAnswers++;
                    }
                }
            }

            $hasTextQuestions = $assignment->exam->questions()
                ->where('type', 'text')
                ->exists();

            $finalStatus = $hasTextQuestions ? 'graded' : 'graded';

            $assignment->update([
                'score' => $totalScore,
                'status' => $finalStatus,
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
     * Calculer le score automatique
     * 
     * @deprecated Utiliser directement ScoringService::calculateAutoCorrectableScore()
     */
    public function calculateAutoScore(ExamAssignment $assignment): float
    {
        return $this->scoringService->calculateAutoCorrectableScore($assignment);
    }

    /**
     * Calculer le score pour une question spécifique
     * 
     * @deprecated Utiliser ScoringService::calculateQuestionScore()
     */
    private function calculateQuestionScore(ExamAssignment $assignment, $question): float
    {
        $answers = $assignment->answers()
            ->where('question_id', $question->id)
            ->with('choice')
            ->get();

        return $this->scoringService->calculateQuestionScore($question, $answers);
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
            $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

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
                    'feedback' => $validatedData['feedback'] ?? $validatedData['teacher_notes'] ?? null
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
