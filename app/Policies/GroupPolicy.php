<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;
// use Illuminate\Auth\Access\Response;

/**
 * GroupPolicy - Policies for managing access to Group model.
 * 
 * This policy class defines authorization logic for various actions
 * related to the Group model, such as viewing, creating, updating,
 * deleting, and managing group students and exam assignments.
 * 
 * @package App\Policies
 */
class GroupPolicy
{
    /**
     * Determine whether the given user is allowed to view any groups.
     * 
     * This method checks if the user has the necessary permissions
     * to view any groups.
     * 
     * @param User $user The user attempting to view groups.
     * @return bool True if the user is authorized to view any groups, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view groups');
    }

    /**
     * Determine whether the given user is allowed to view a specific group.
     * 
     * This method checks if the user has the necessary permissions
     * to view the specified group.
     * 
     * @param User $user The user attempting to view the group.
     * @param Group $group The group instance to be viewed.
     * @return bool True if the user is authorized to view the group, false otherwise.
     */
    public function view(User $user, Group $group): bool
    {
        return $user->can('view groups');
    }

    /**
     * Determine whether the given user is allowed to create groups.
     * 
     * This method checks if the user has the necessary permissions
     * to create a new group.
     * 
     * @param User $user The user attempting to create a group.
     * @return bool True if the user is authorized to create groups, false otherwise.
     */
    public function create(User $user): bool
    {
        return $user->can('create groups');
    }

    /**
     * Determine whether the given user is allowed to update a group.
     * 
     * This method checks if the user has the necessary permissions
     * to update the specified group.
     * 
     * @param User $user The user attempting to update the group.
     * @param Group $group The group instance to be updated.
     * @return bool True if the user is authorized to update the group, false otherwise.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->can('update groups');
    }

    /**
     * Determine whether the given user is allowed to delete a group.
     * 
     * This method checks if the user has the necessary permissions
     * to delete the specified group.
     * 
     * @param User $user The user attempting to delete the group.
     * @param Group $group The group instance to be deleted.
     * @return bool True if the user is authorized to delete the group, false otherwise.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->can('delete groups');
    }

    /**
     * Determine whether the given user is allowed to manage students in a group.
     * 
     * This method checks if the user has the necessary permissions
     * to manage students within the specified group.
     * 
     * @param User $user The user attempting to manage students.
     * @param Group $group The group instance.
     * @return bool True if the user is authorized to manage students, false otherwise.
     */
    public function manageStudents(User $user, Group $group): bool
    {
        return $user->can('manage group students');
    }

    /**
     * Determine whether the given user is allowed to assign exams to a group.
     * 
     * This method checks if the user has the necessary permissions
     * to assign exams to the specified group.
     * 
     * @param User $user The user attempting to assign exams.
     * @param Group $group The group instance.
     * @return bool True if the user is authorized to assign exams, false otherwise.
     */
    public function assignExams(User $user, Group $group): bool
    {
        return $user->can('assign group exams');
    }

    /**
     * Determine whether the given user is allowed to toggle the status of a group.
     * 
     * This method checks if the user has the necessary permissions
     * to toggle the status of the specified group.
     * 
     * @param User $user The user attempting to toggle the group status.
     * @param Group $group The group instance.
     * @return bool True if the user is authorized to toggle the group status, false otherwise.
     */
    public function toggleStatus(User $user, Group $group): bool
    {
        return $user->can('toggle group status');
    }
}
