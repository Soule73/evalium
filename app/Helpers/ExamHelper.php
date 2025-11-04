<?php

namespace App\Helpers;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Exam Helper - Pure utility functions for exam-related operations
 * 
 * Following SOLID principles:
 * - Single Responsibility: Only utility functions, no state
 * - No dependencies injection needed (static methods)
 * - Can be used anywhere in the application
 */
class ExamHelper
{
    /**
     * Statuses indicating an exam has been completed
     *
     * @var string[]
     */
    public const EXAM_COMPLETED_STATUSES = ['submitted', 'graded'];

    /**
     * Filter assignments to include only active ones (in progress)
     *
     * @param Collection<int, ExamAssignment> $assignments
     * @return Collection<int, ExamAssignment>
     */
    public static function filterActiveAssignments(Collection $assignments): Collection
    {
        return $assignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        });
    }

    /**
     * Filter assignments to return only completed ones
     *
     * @param Collection<int, ExamAssignment> $assignments
     * @return Collection<int, ExamAssignment>
     */
    public static function filterCompletedAssignments(Collection $assignments): Collection
    {
        return $assignments->whereIn('status', self::EXAM_COMPLETED_STATUSES);
    }

    /**
     * Filter assignments to return only not started ones
     *
     * @param Collection<int, ExamAssignment> $assignments
     * @return Collection<int, ExamAssignment>
     */
    public static function filterNotStartedAssignments(Collection $assignments): Collection
    {
        return $assignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        });
    }

    /**
     * Determine if a student can take an exam
     *
     * @param Exam $exam
     * @param ExamAssignment|null $assignment
     * @return bool
     */
    public static function canTakeExam(Exam $exam, ?ExamAssignment $assignment): bool
    {
        return $assignment &&
            $assignment->submitted_at === null &&
            self::isExamActive($exam);
    }

    /**
     * Check if exam is currently active (private helper)
     *
     * @param Exam $exam
     * @return bool
     */
    private static function isExamActive(Exam $exam): bool
    {
        return $exam->is_active;
    }
}
