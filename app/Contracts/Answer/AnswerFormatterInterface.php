<?php

namespace App\Contracts\Answer;

use App\Models\AssessmentAssignment;

/**
 * Interface for Assessment answer formatting.
 *
 * Defines the contract for formatting student answers
 * in different formats (frontend display, export, etc.).
 */
interface AnswerFormatterInterface
{
    /**
     * Format answers from an assignment for frontend display.
     *
     * @param  AssessmentAssignment  $assignment  The assignment containing the answers
     * @return array The formatted answers
     */
    public function formatForFrontend(AssessmentAssignment $assignment): array;

    /**
     * Format a single answer (single choice or text question).
     *
     * @param  mixed  $answer  The answer to format
     * @return array The formatted answer
     */
    public function formatSingleAnswer($answer): array;

    /**
     * Format multiple answers (multiple choice questions).
     *
     * @param  \Illuminate\Support\Collection  $answers  The answers to format
     * @return array The formatted answers
     */
    public function formatMultipleAnswers($answers): array;

    /**
     * Check if an assignment has any answers.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to check
     * @return bool True if the assignment has answers
     */
    public function hasAnswers(AssessmentAssignment $assignment): bool;

    /**
     * Count the number of answered questions.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to analyze
     * @return int Number of questions with answers
     */
    public function countAnsweredQuestions(AssessmentAssignment $assignment): int;

    /**
     * Get completion statistics for an assignment.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to analyze
     * @return array Statistics including answered/total questions, completion percentage
     */
    public function getCompletionStats(AssessmentAssignment $assignment): array;
}
