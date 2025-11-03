<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Answer;
// use Illuminate\Auth\Access\Response;

/**
 * AnswerPolicy - Policies for managing access to Answer model.
 *
 * This policy class defines authorization logic for various actions
 * related to the Answer model, such as viewing, creating, updating,
 * deleting, restoring, and force deleting answers.
 *
 * @package App\Policies
 */
class AnswerPolicy
{
    /**
     * Determine whether the given user is allowed to view any answers.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view any answers,
     *
     * @param User $user The user attempting to view answers.
     * @return bool True if the user is authorized to view any answers, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view answers');
    }


    /**
     * Determine whether the given user is allowed to view the specified answer.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view any answers,
     * - whether the user owns the answer,
     *
     * @param User $user The user attempting to view the answer.
     * @param Answer $answer The answer instance to be viewed.
     * @return bool True if the user is authorized to view the answer, false otherwise.
     */
    public function view(User $user, Answer $answer): bool
    {
        if ($user->hasRole('student')) {
            return $answer->assignment && $answer->assignment->student_id === $user->id;
        }

        return $user->can('view answers');
    }

    /**
     * Determine whether the given user is allowed to create answers.
     * 
     * Typical checks performed by this policy may include:
     * - whether the user has permission to create answers,
     * 
     * @param User $user The user attempting to create an answer.
     * @return bool True if the user is authorized to create an answer, false otherwise.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('student')) {
            return true;
        }

        return $user->can('create answers');
    }

    /**
     * Determine whether the given user is allowed to update the specified answer.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to update answers,
     * - whether the user owns the answer,
     *
     * @param User $user The user attempting to update the answer.
     * @param Answer $answer The answer instance to be updated.
     * @return bool True if the user is authorized to update the answer, false otherwise.
     */
    public function update(User $user, Answer $answer): bool
    {
        if ($user->hasRole('student')) {
            return $answer->assignment &&
                $answer->assignment->student_id === $user->id;
        }

        return $user->can('update answers');
    }

    /**
     * Determine whether the given user is allowed to delete the specified answer.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to delete answers,
     * - whether the user owns the answer,
     *
     * @param User $user The user attempting to delete the answer.
     * @param Answer $answer The answer instance to be deleted.
     * @return bool True if the user is authorized to delete the answer, false otherwise.
     */
    public function delete(User $user, Answer $answer): bool
    {
        if ($user->hasRole('student')) {
            return $answer->assignment &&
                $answer->assignment->student_id === $user->id;
        }

        return $user->can('delete answers');
    }

    /**
     * Determine whether the given user is allowed to grade the specified answer.
     * 
     * Typical checks performed by this policy may include:
     * - whether the user has permission to grade answers,
     * 
     * @param User $user The user attempting to grade the answer.
     * @param Answer $answer The answer instance to be graded.
     * @return bool True if the user is authorized to grade the answer, false otherwise.
     */
    public function grade(User $user, Answer $answer): bool
    {
        return $user->can('grade answers');
    }
}
