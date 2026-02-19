<?php

namespace App\Services\Admin;

use App\Contracts\Services\UserManagementServiceInterface;
use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * User Management Service - Handle user CRUD operations and role assignments
 *
 * Single Responsibility: Manage user lifecycle and role assignments
 */
class UserManagementService implements UserManagementServiceInterface
{
    /**
     * Create a new user with random password and send credentials notification
     *
     * @param  array  $data  User data (name, email, role)
     */
    public function store(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $password = Str::random(12);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $user->assignRole($data['role']);

            $user->notify(new UserCredentialsNotification($password, $data['role']));

            return $user;
        });
    }

    /**
     * Update existing user data and role
     *
     * @param  User  $user  User to update
     * @param  array  $data  Updated data (name, email, password optional, role)
     *
     * @throws \InvalidArgumentException
     */
    public function update(User $user, array $data): void
    {
        try {
            DB::transaction(function () use ($user, $data) {
                $updatedData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];

                if (isset($data['password']) && $data['password']) {
                    $updatedData['password'] = Hash::make($data['password']);
                }

                $user->update($updatedData);

                if (! isset($data['role']) || ! Role::where('name', $data['role'])->exists()) {
                    throw new \InvalidArgumentException('Role is required for update.');
                }

                $user->syncRoles([$data['role']]);
            });
        } catch (\Exception $e) {
            Log::error('Error updating user: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete a user
     *
     * @param  User  $user  User to delete
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Toggle user active status
     *
     * @param  User  $user  User to toggle
     */
    public function toggleStatus(User $user): void
    {
        $user->is_active = ! $user->is_active;
        $user->save();
    }

    /**
     * Restore a soft-deleted user
     *
     * @param  int  $userId  User ID to restore
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreUser(int $userId): User
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        return $user;
    }

    /**
     * Permanently delete a user from database
     *
     * @param  int  $userId  User ID to force delete
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forceDeleteUser(int $userId): bool
    {
        $user = User::withTrashed()->findOrFail($userId);

        return $user->forceDelete();
    }

    /**
     * Check if user is a teacher
     *
     * @param  User  $user  User to check
     */
    public function isTeacher(User $user): bool
    {
        return $user->hasRole('teacher');
    }

    /**
     * Load user roles if not already loaded
     *
     * @param  User  $user  User to load roles for
     */
    public function ensureRolesLoaded(User $user): void
    {
        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }
    }
}
