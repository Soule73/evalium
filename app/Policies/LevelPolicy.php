<?php

namespace App\Policies;

use App\Models\Level;
use App\Models\User;

// use Illuminate\Auth\Access\Response;

/**
 * LevelPolicy - Policies for managing access to Level model.
 *
 * This policy class defines authorization logic for various actions
 * related to the Level model, such as viewing, creating, updating,
 * deleting, and managing levels.
 */
class LevelPolicy
{
    /**
     * Determine whether the given user is allowed to view any levels.
     *
     * This method checks if the user has the necessary permissions
     * to view any levels.
     *
     * @param  User  $user  The user attempting to view levels.
     * @return bool True if the user is authorized to view any levels, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view levels');
    }

    /**
     * Determine whether the given user is allowed to view a specific level.
     *
     * This method checks if the user has the necessary permissions
     * to view the specified level.
     *
     * @param  User  $user  The user attempting to view the level.
     * @param  Level  $level  The level instance to be viewed.
     * @return bool True if the user is authorized to view the level, false otherwise.
     */
    public function view(User $user, Level $level): bool
    {
        return $user->can('view levels');
    }

    /**
     * Determine whether the given user is allowed to create levels.
     *
     * This method checks if the user has the necessary permissions
     * to create a new level.
     *
     * @param  User  $user  The user attempting to create a level.
     * @return bool True if the user is authorized to create levels, false otherwise.
     */
    public function create(User $user): bool
    {
        return $user->can('create levels');
    }

    /**
     * Determine whether the given user is allowed to update a specific level.
     *
     * This method checks if the user has the necessary permissions
     * to update the specified level.
     *
     * @param  User  $user  The user attempting to update the level.
     * @param  Level  $level  The level instance to be updated.
     * @return bool True if the user is authorized to update the level, false otherwise.
     */
    public function update(User $user, Level $level): bool
    {
        return $user->can('update levels');
    }

    /**
     * Determine whether the given user is allowed to delete a specific level.
     *
     * This method checks if the user has the necessary permissions
     * to delete the specified level.
     *
     * @param  User  $user  The user attempting to delete the level.
     * @param  Level  $level  The level instance to be deleted.
     * @return bool True if the user is authorized to delete the level, false otherwise.
     */
    public function delete(User $user, Level $level): bool
    {
        return $user->can('delete levels');
    }

    /**
     * Determine whether the given user is allowed to manage levels.
     *
     * This method checks if the user has the necessary permissions
     * to manage levels.
     *
     * @param  User  $user  The user attempting to manage levels.
     * @return bool True if the user is authorized to manage levels, false otherwise.
     */
    public function manage(User $user): bool
    {
        return $user->can('update levels') || $user->can('delete levels');
    }

    /**
     * Determine whether the given user is allowed to toggle the status of a specific level.
     *
     * This method checks if the user has the necessary permissions
     * to activate or deactivate the specified level.
     *
     * @param  User  $user  The user attempting to toggle the level status.
     * @param  Level  $level  The level instance whose status is to be toggled.
     * @return bool True if the user is authorized to toggle the level status, false otherwise.
     */
    public function toggleStatus(User $user, Level $level): bool
    {
        return $user->can('update levels');
    }
}
