<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Defines authorization rules for the Enrollment resource.
 *
 * Covers view, create, update, delete and transfer permissions
 * for admin and related roles.
 */
class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view enrollments');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->can('view enrollments');
    }

    public function create(User $user): bool
    {
        return $user->can('create enrollments');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->can('update enrollments');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->can('delete enrollments');
    }

    public function transfer(User $user, Enrollment $enrollment): bool
    {
        return $user->can('transfer enrollments');
    }
}
