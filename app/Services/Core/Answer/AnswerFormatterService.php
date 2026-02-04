<?php

namespace App\Services\Core\Answer;

use App\Contracts\Answer\AnswerFormatterInterface;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use Illuminate\Support\Collection;

/**
 * Centralized service for assessment answer formatting.
 *
 *
 * Responsibilities:
 * - Format answers for frontend display
 * - Handle different answer types (single, multiple, text)
 * - Calculate completion statistics
 * - Provide answer management utilities
 * - Retrieve and format student results and review data
 */
class AnswerFormatterService implements AnswerFormatterInterface
{
    /**
     * Format assignment answers for frontend display.
     *
     *
     * @param  AssessmentAssignment  $assignment  The assignment containing the answers
     * @return array Formatted answers, grouped by question_id
     */
    public function formatForFrontend(AssessmentAssignment $assignment): array
    {
        $answers = $assignment->relationLoaded('answers')
            ? $assignment->answers
            : $assignment->answers()->with(['choice', 'question'])->get();

        return $answers
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                if ($questionAnswers->count() === 1) {
                    return $this->formatSingleAnswer($questionAnswers->first());
                }

                return $this->formatMultipleAnswers($questionAnswers);
            })
            ->toArray();
    }

    /**
     * Format a single answer (one_choice, boolean, text).
     *
     * @param  mixed  $answer  The Answer object
     * @return array Formatted answer with all metadata
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
     * Format multiple answers (multiple choice questions).
     *
     * @param  Collection  $answers  Collection of answers for the same question
     * @return array Formatted answers as an array of choices
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
     * Check if an assignment has at least one answer.
     *
     * @return bool True if the assignment has any answers
     */
    public function hasAnswers(AssessmentAssignment $assignment): bool
    {
        return $assignment->answers()->exists();
    }

    /**
     * Count the number of distinct answered questions.
     *
     * @return int Number of questions with at least one answer
     */
    public function countAnsweredQuestions(AssessmentAssignment $assignment): int
    {
        return $assignment->answers()
            ->distinct('question_id')
            ->count('question_id');
    }

    /**
     * Get assignment completion statistics.
     *
     * @return array Statistics including total, answered, and completion percentage
     */
    public function getCompletionStats(AssessmentAssignment $assignment): array
    {
        $assessment = $assignment->relationLoaded('assessment') ? $assignment->assessment : $assignment->assessment()->first();

        $totalQuestions = $assessment->relationLoaded('questions')
            ? $assessment->questions->count()
            : $assessment->questions()->count();

        $answeredQuestions = $assignment->relationLoaded('answers')
            ? $assignment->answers->pluck('question_id')->unique()->count()
            : $assignment->answers()->distinct('question_id')->count('question_id');

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
     * Get complete data for displaying student results.
     *
     *
     * @return array Formatted data with assignment, student, assessment, and answers
     */
    public function getStudentResultsData(AssessmentAssignment $assignment): array
    {
        $assignment->load([
            'answers.question.choices',
            'answers.choice',
            'assessment.questions.choices',
            'student',
        ]);

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'assessment' => $assignment->assessment,
            'userAnswers' => $this->formatForFrontend($assignment),
            'stats' => $this->getCompletionStats($assignment),
        ];
    }

    /**
     * Prepare answer data based on question type.
     *
     * Replaces AssessmentSessionService::prepareAnswerData()
     *
     * @param  string  $questionType  Question type (multiple, one_choice, boolean, text)
     * @param  array  $requestData  Request data containing the answer
     * @return array Prepared data for insertion
     */
    public function prepareAnswerData(string $questionType, array $requestData): array
    {
        if (in_array($questionType, ['multiple', 'one_choice', 'boolean'])) {
            return [
                'choice_id' => $requestData['choice_id'] ?? null,
                'answer_text' => null,
            ];
        }

        return [
            'answer_text' => $requestData['answer_text'] ?? '',
            'choice_id' => null,
        ];
    }
}
