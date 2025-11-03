<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Question;
// use Illuminate\Auth\Access\Response;

/**
 * QuestionPolicy - Policies for managing access to Question model. 
 * 
 * This policy class defines authorization logic for various actions
 * related to the Question model, such as viewing, creating, updating,
 * and deleting questions.
 * 
 * @package App\Policies 
 */
class QuestionPolicy
{
    /**
     * Determine whether the given user is allowed to view any questions.
     * 
     * This method checks if the user has the necessary permissions
     * to view any questions.
     * 
     * @param User $user The user attempting to view questions.
     * @return bool True if the user is authorized to view any questions, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view questions');
    }

    /**
     * Determine whether the given user is allowed to view a specific question.
     * 
     * This method checks if the user has the necessary permissions
     * to view the specified question.
     * 
     * @param User $user The user attempting to view the question.
     * @param Question $question The question instance to be viewed.
     * @return bool True if the user is authorized to view the question, false otherwise.
     */
    public function view(User $user, Question $question): bool
    {
        return $user->can('view questions');
    }

    /**
     * Determine whether the given user is allowed to create questions.
     * 
     * This method checks if the user has the necessary permissions
     * to create questions.
     * 
     * @param User $user The user attempting to create questions.
     * @return bool True if the user is authorized to create questions, false otherwise.
     */

    public function create(User $user): bool
    {
        return $user->can('create questions');
    }

    /**
     * Determine whether the given user is allowed to update a question.
     * 
     * This method checks if the user has the necessary permissions
     * to update the specified question.
     * 
     * @param User $user The user attempting to update the question.
     * @param Question $question The question instance to be updated.
     * @return bool True if the user is authorized to update the question, false otherwise.
     */
    public function update(User $user, Question $question): bool
    {
        return $user->can('update questions');
    }

    /**
     * Determine whether the given user is allowed to delete a question.
     * 
     * This method checks if the user has the necessary permissions
     * to delete the specified question.
     * 
     * @param User $user The user attempting to delete the question.
     * @param Question $question The question instance to be deleted.
     * @return bool True if the user is authorized to delete the question, false otherwise.
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->can('delete questions');
    }
}
