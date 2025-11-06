<?php

namespace App\Contracts\Answer;

use App\Models\ExamAssignment;

/**
 * Interface for exam answer formatting.
 *
 * Defines the contract for formatting student answers
 * in different formats (frontend display, export, etc.).
 */
interface AnswerFormatterInterface
{
    /**
     * Format answers from an assignment for frontend display.
     *
     * @param  ExamAssignment  $assignment  The assignment containing the answers
     * @return array The formatted answers
     */
    public function formatForFrontend(ExamAssignment $assignment): array;

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
     * @param  ExamAssignment  $assignment  The assignment to check
     * @return bool True if the assignment has answers
     */
    public function hasAnswers(ExamAssignment $assignment): bool;

    /**
     * Count the number of answered questions.
     *
     * @param  ExamAssignment  $assignment  The assignment to analyze
     * @return int Number of questions with answers
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int;

    /**
     * Get completion statistics for an assignment.
     *
     * @param  ExamAssignment  $assignment  The assignment to analyze
     * @return array Statistics including answered/total questions, completion percentage
     */
    public function getCompletionStats(ExamAssignment $assignment): array;
}
