<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing exam scoring and teacher corrections.
 *
 * Handles manual grading, score recalculation, and assignment status updates.
 */
class ExamScoringService
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Save manual corrections from a teacher (unified method).
     *
     * Supports multiple input formats:
     * - Array of scores: [questionId => ['score' => X, 'feedback' => Y]]
     * - Array of scores: [questionId => scoreValue]
     * - Batch format: ['scores' => [['question_id' => X, 'score' => Y, 'feedback' => Z]]]
     * - Single format: ['question_id' => X, 'score' => Y, 'feedback' => Z]
     *
     * Automatically updates assignment status to 'graded' after corrections.
     *
     * @param  ExamAssignment  $assignment  Assignment to correct
     * @param  array  $data  Question scores and feedback
     * @param  string|null  $teacherNotes  Optional teacher notes for the assignment
     * @return array Summary with total score and updated count
     */
    public function saveCorrections(ExamAssignment $assignment, array $data, ?string $teacherNotes = null): array
    {
        return DB::transaction(function () use ($assignment, $data, $teacherNotes) {
            $normalizedScores = $this->normalizeScoresInput($data);

            $answers = $assignment->answers()->get()->keyBy('question_id');
            $updatedAnswers = 0;

            foreach ($normalizedScores as $questionId => $scoreData) {
                $answer = $answers->get($questionId);

                if ($answer) {
                    $updateData = ['score' => $scoreData['score']];

                    if (isset($scoreData['feedback']) && $scoreData['feedback'] !== null) {
                        $updateData['feedback'] = $scoreData['feedback'];
                    }

                    $answer->update($updateData);
                    $updatedAnswers++;
                }
            }

            $totalScore = $assignment->answers()->sum('score');

            $assignmentUpdateData = [
                'score' => $totalScore,
                'status' => 'graded',
            ];

            if ($teacherNotes !== null) {
                $assignmentUpdateData['teacher_notes'] = $teacherNotes;
            }

            $assignment->update($assignmentUpdateData);

            return [
                'success' => true,
                'assignment_id' => $assignment->id,
                'updated_count' => $updatedAnswers,
                'total_score' => $totalScore,
                'status' => 'graded',
            ];
        });
    }

    /**
     * Save manual corrections from a teacher (legacy method - deprecated).
     *
     * @deprecated Use saveCorrections() instead
     *
     * @param  ExamAssignment  $assignment  Assignment to correct
     * @param  array  $scores  Question scores and feedback
     * @return array Summary with total score and updated count
     */
    public function saveTeacherCorrections(ExamAssignment $assignment, array $scores): array
    {
        return $this->saveCorrections($assignment, $scores);
    }

    /**
     * Recalculate all automatic scores for an exam.
     *
     * Useful after exam questions or scoring rules are modified.
     * Only processes submitted assignments.
     *
     * @param  Exam  $exam  The exam to recalculate scores for
     * @return array Statistics with total and updated assignment counts
     */
    public function recalculateExamScores(Exam $exam): array
    {
        $assignments = $exam->assignments()
            ->whereNotNull('submitted_at')
            ->get();

        $updated = 0;

        foreach ($assignments as $assignment) {
            $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

            if ($assignment->auto_score !== $autoScore) {
                $assignment->update(['auto_score' => $autoScore]);
                $updated++;
            }
        }

        return [
            'total_assignments' => $assignments->count(),
            'updated_count' => $updated,
        ];
    }

    /**
     * Save manual correction from a teacher (legacy method - deprecated).
     *
     * @deprecated Use saveCorrections() instead
     *
     * @param  Exam  $exam  Exam being corrected
     * @param  \App\Models\User  $student  Student whose work is corrected
     * @param  array  $validatedData  Scores and feedback data
     * @return array Summary with updated count and scores
     */
    public function saveManualCorrection($exam, $student, array $validatedData): array
    {
        $assignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->firstOrFail();

        $teacherNotes = $validatedData['teacher_notes'] ?? null;

        return $this->saveCorrections($assignment, $validatedData, $teacherNotes);
    }

    /**
     * Normalize various score input formats into a consistent structure.
     *
     * Handles multiple input formats:
     * - [questionId => ['score' => X, 'feedback' => Y]]
     * - [questionId => scoreValue]
     * - ['scores' => [['question_id' => X, 'score' => Y, 'feedback' => Z]]]
     * - ['question_id' => X, 'score' => Y, 'feedback' => Z]
     *
     * @param  array  $data  Raw input data
     * @return array Normalized format [questionId => ['score' => X, 'feedback' => Y]]
     */
    private function normalizeScoresInput(array $data): array
    {
        $normalized = [];

        if (isset($data['scores']) && is_array($data['scores'])) {
            foreach ($data['scores'] as $scoreData) {
                if (isset($scoreData['question_id']) && isset($scoreData['score'])) {
                    $normalized[$scoreData['question_id']] = [
                        'score' => $scoreData['score'],
                        'feedback' => $scoreData['feedback'] ?? null,
                    ];
                }
            }
        } elseif (isset($data['question_id']) && isset($data['score'])) {
            $normalized[$data['question_id']] = [
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? $data['teacher_notes'] ?? null,
            ];
        } else {
            foreach ($data as $questionId => $scoreData) {
                if (is_numeric($questionId)) {
                    if (is_array($scoreData)) {
                        $normalized[$questionId] = [
                            'score' => $scoreData['score'] ?? $scoreData,
                            'feedback' => $scoreData['feedback'] ?? $scoreData['teacher_notes'] ?? null,
                        ];
                    } else {
                        $normalized[$questionId] = [
                            'score' => $scoreData,
                            'feedback' => null,
                        ];
                    }
                }
            }
        }

        return $normalized;
    }
}
