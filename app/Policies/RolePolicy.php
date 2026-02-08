<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * RolePolicy - Authorization policies for Role management
 *
 * Defines who can view, create, update, and delete roles
 */
class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view roles');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('view roles');
    }

    /**
     * Determine whether the user can create roles.
     * Roles are predefined - creation is disabled.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('update roles');
    }

    /**
     * Determine whether the user can delete the role.
     * Roles are predefined - deletion is disabled.
     */
    public function delete(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the role.
     */
    public function restore(User $user, Role $role): bool
    {
        return $user->can('update roles');
    }

    /**
     * Determine whether the user can permanently delete the role.
     * Roles are predefined - force deletion is disabled.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
