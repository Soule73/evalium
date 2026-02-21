<?php

namespace App\Contracts\Services;

use App\Models\User;

interface UserManagementServiceInterface
{
    /**
     * Create a new user and optionally send credentials via notification.
     *
     * @return array{user: \App\Models\User, password: string}
     */
    public function store(array $data): array;

    /**
     * Update an existing user's data and role.
     */
    public function update(User $user, array $data): void;

    /**
     * Soft delete a user.
     */
    public function delete(User $user): void;

    /**
     * Toggle the active status of a user.
     */
    public function toggleStatus(User $user): void;

    /**
     * Restore a soft-deleted user.
     */
    public function restoreUser(int $userId): User;

    /**
     * Permanently delete a user from the database.
     */
    public function forceDeleteUser(int $userId): bool;

    /**
     * Check if a user has the teacher role.
     */
    public function isTeacher(User $user): bool;

    /**
     * Check if a teacher has active class assignments in the current academic year.
     */
    public function isTeachingInCurrentYear(User $teacher): bool;

    /**
     * Load user roles relationship if not already loaded.
     */
    public function ensureRolesLoaded(User $user): void;
}
