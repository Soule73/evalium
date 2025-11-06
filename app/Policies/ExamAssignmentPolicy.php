<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;

// use Illuminate\Auth\Access\Response;

/**
 * ExamAssignmentPolicy - Policies for managing access to ExamAssignment model.
 *
 * This policy class defines authorization logic for various actions
 * related to the ExamAssignment model, such as viewing, creating, updating,
 * deleting, and submitting assignments.
 */
class ExamAssignmentPolicy
{
    /**
     * Determine whether the given user is allowed to view any exam assignments.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view any exam assignments,
     *
     * @param  User  $user  The user attempting to view exam assignments.
     * @return bool True if the user is authorized to view any exam assignments, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('student')) {
            return true;
        }

        return $user->can('view any exams');
    }

    /**
     * Determine whether the given user is allowed to view the specified exam assignment.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view the assignment,
     * - whether the user owns the assignment,
     *
     * @param  User  $user  The user attempting to view the assignment.
     * @param  ExamAssignment  $assignment  The exam assignment instance to be viewed.
     * @return bool True if the user is authorized to view the assignment, false otherwise.
     */
    public function view(User $user, ExamAssignment $assignment): bool
    {
        if ($user->hasRole('student')) {
            return $assignment->student_id === $user->id;
        }

        $exam = $assignment->exam;

        return $user->can('view any exams') || $exam->teacher_id === $user->id;
    }

    /**
     * Determine whether the given user is allowed to create exam assignments.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to create assignments,
     *
     * @param  User  $user  The user attempting to create an answer.
     * @return bool True if the user is authorized to create an answer, false otherwise.
     */
    public function create(User $user): bool
    {
        return $user->can('assign exams');
    }

    /**
     * Determine whether the given user is allowed to update the specified exam assignment.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to update the assignment,
     * - whether the user owns the assignment,
     *
     * @param  User  $user  The user attempting to update the assignment.
     * @param  ExamAssignment  $assignment  The exam assignment instance to be updated.
     * @return bool True if the user is authorized to update the assignment, false otherwise.
     */
    public function update(User $user, ExamAssignment $assignment): bool
    {
        if ($user->hasRole('student')) {
            return $assignment->student_id === $user->id;
        }

        $exam = $assignment->exam;

        return $user->can('view any exams') || $exam->teacher_id === $user->id;
    }

    /**
     * Determine whether the given user is allowed to delete the specified exam assignment.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to delete the assignment,
     * - whether the user owns the assignment,
     *
     * @param  User  $user  The user attempting to delete the assignment.
     * @param  ExamAssignment  $assignment  The exam assignment instance to be deleted.
     * @return bool True if the user is authorized to delete the assignment, false otherwise.
     */
    public function delete(User $user, ExamAssignment $assignment): bool
    {
        if ($user->hasRole('student')) {
            return $assignment->student_id === $user->id;
        }

        $exam = $assignment->exam;

        return $user->can('view any exams') || $exam->teacher_id === $user->id;
    }

    /**
     * Determine whether the given user is allowed to submit the specified exam assignment.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to submit the assignment,
     * - whether the user owns the assignment,
     *
     * @param  User  $user  The user attempting to submit the assignment.
     * @param  ExamAssignment  $assignment  The exam assignment instance to be submitted.
     * @return bool True if the user is authorized to submit the assignment, false otherwise.
     */
    public function submit(User $user, ExamAssignment $assignment): bool
    {
        if (! $user->hasRole('student')) {
            return false;
        }

        return $assignment->student_id === $user->id &&
            $assignment->started_at !== null &&
            $assignment->submitted_at === null;
    }

    /**
     * Determine whether the given user is allowed to grade the specified exam assignment.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to grade the assignment,
     *
     * @param  User  $user  The user attempting to grade the assignment.
     * @param  ExamAssignment  $assignment  The exam assignment instance to be graded.
     * @return bool True if the user is authorized to grade the assignment, false otherwise.
     */
    public function grade(User $user, ExamAssignment $assignment): bool
    {
        if ($user->hasRole('student')) {
            return false;
        }

        return $user->can('correct exams');
    }

    /**
     * Determine if a student can take an exam
     *
     * @param  User  $user  The user attempting to take the exam.
     * @param  ExamAssignment  $assignment  The exam assignment instance.
     */
    public function canTake(User $user, ExamAssignment $assignment): bool
    {
        return $assignment->student_id === $user->id &&
            $assignment->submitted_at === null &&
            ! $assignment->forced_submission;
    }
}
