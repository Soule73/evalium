<?php

namespace App\Policies;

use App\Models\User;
// use Illuminate\Auth\Access\Response;

/**
 * UserPolicy - Policies for managing access to User model.
 * 
 * This policy class defines authorization logic for various actions
 * related to the User model, such as viewing, creating, updating,
 * deleting, restoring, and force deleting users.
 * 
 * @package App\Policies
 */
class UserPolicy
{
    /**
     * Determine whether the given user is allowed to view any users.
     * 
     * This method checks if the user has the necessary permissions
     * to view any users.
     * 
     * @param User $user The user attempting to view users.
     * @return bool True if the user is authorized to view any users, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    /**
     * Determine whether the given user is allowed to view a specific user.
     * 
     * This method checks if the user has the necessary permissions
     * to view the specified user.
     * 
     * @param User $user The user attempting to view the user.
     * @param User $model The user instance to be viewed.
     * @return bool True if the user is authorized to view the user, false otherwise.
     */

    public function view(User $user, User $model): bool
    {
        return $user->can('view users') || $user->id === $model->id;
    }

    /**
     * Determine whether the given user is allowed to create users.
     * 
     * This method checks if the user has the necessary permissions
     * to create users. 
     * 
     * @param User $user The user attempting to create users.
     * @return bool True if the user is authorized to create users, false otherwise.
     */
    public function create(User $user): bool
    {
        return $user->can('create users');
    }

    /**
     * Determine whether the given user is allowed to update a user.
     * 
     * This method checks if the user has the necessary permissions
     * to update the specified user.
     * 
     * @param User $user The user attempting to update the user.
     * @param User $model The user instance to be updated.
     * @return bool True if the user is authorized to update the user, false otherwise.
     */
    public function update(User $user, User $model): bool
    {

        return $user->can('update users') || $user->id === $model->id;
    }

    /**
     * Determine whether the given user is allowed to delete a user.
     * 
     * This method checks if the user has the necessary permissions
     * to delete the specified user.
     * 
     * @param User $user The user attempting to delete the user.
     * @param User $model The user instance to be deleted.
     * @return bool True if the user is authorized to delete the user, false otherwise.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('delete users');
    }

    /**
     * Determine whether the given user is allowed to restore a user.
     * 
     * This method checks if the user has the necessary permissions
     * to restore the specified user.
     * 
     * @param User $user The user attempting to restore the user.
     * @param User $model The user instance to be restored.
     * @return bool True if the user is authorized to restore the user, false otherwise.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('restore users');
    }

    /**
     * Determine whether the given user is allowed to permanently delete a user.
     * 
     * This method checks if the user has the necessary permissions
     * to permanently delete the specified user.
     * 
     * @param User $user The user attempting to permanently delete the user.
     * @param User $model The user instance to be permanently deleted.
     * @return bool True if the user is authorized to permanently delete the user, false otherwise.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('force delete users');
    }

    /**
     * Determine whether the given user is allowed to manage students.
     * 
     * This method checks if the user has the necessary permissions
     * to manage students.
     * 
     * @param User $user The user attempting to manage students.
     * @return bool True if the user is authorized to manage students, false otherwise.
     */
    public function manageStudents(User $user): bool
    {
        return $user->can('manage students');
    }

    /**
     * Determine whether the given user is allowed to manage teachers.
     * 
     * This method checks if the user has the necessary permissions
     * to manage teachers.
     * 
     * @param User $user The user attempting to manage teachers.
     * @return bool True if the user is authorized to manage teachers, false otherwise.
     */
    public function manageTeachers(User $user): bool
    {
        return $user->can('update users');
    }

    /**
     * Determine whether the given user is allowed to manage admins.
     * 
     * This method checks if the user has the necessary permissions
     * to manage admins.
     * 
     * @param User $user The user attempting to manage admins.
     * @return bool True if the user is authorized to manage admins, false otherwise.
     */
    public function manageAdmins(User $user): bool
    {
        return $user->can('update users');
    }

    /**
     * Determine whether the given user is allowed to toggle the status of a user.
     * 
     * This method checks if the user has the necessary permissions
     * to toggle the status of the specified user.
     * 
     * @param User $user The user attempting to toggle the status.
     * @param User $model The user instance whose status is to be toggled.
     * @return bool True if the user is authorized to toggle the status, false otherwise.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('update users');
    }

    /**
     * Determine whether the given user is a student.
     *
     * @param User $user The user instance to check.
     * @return bool True if the user is a student, false otherwise.
     */
    public function isStudent(User $user): bool
    {
        return $user->hasRole('student');
    }

    /**
     * Determine whether the given user is a teacher.
     *
     * @param User $user The user instance to check.
     * @return bool True if the user is a teacher, false otherwise.
     */
    public function isTeacher(User $user): bool
    {
        return $user->hasRole('teacher');
    }

    /**
     * Determine whether the given user is an admin.
     *
     * @param User $user The user instance to check.
     * @return bool True if the user is an admin, false otherwise.
     */
    public function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the given user is a super admin.
     *
     * @param User $user The user instance to check.
     * @return bool True if the user is a super admin, false otherwise.
     */
    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
