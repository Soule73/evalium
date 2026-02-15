<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use App\Services\Traits\Paginatable;
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
class UserManagementService
{
    use Paginatable;

    /**
     * Get paginated list of users with filtering
     *
     * @param  array  $filters  Filter criteria (role, status, search, exclude_roles, include_deleted)
     * @param  int  $perPage  Number of items per page
     * @param  User  $currentUser  Current authenticated user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserWithPagination(array $filters, int $perPage, User $currentUser)
    {
        $query = User::with('roles')->whereNot('id', $currentUser->id);

        if (! empty($filters['exclude_roles'])) {
            $query->whereDoesntHave('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['exclude_roles']);
            });
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['include_deleted'])) {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $this->paginateQuery($query, $filters['per_page'] ?? 10);
    }

    /**
     * Create a new user with random password and send credentials notification
     *
     * @param  array  $data  User data (name, email, role)
     * @return User
     */
    public function store(array $data)
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
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function update(User $user, array $data)
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
            Log::error('Error updating user: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete a user
     *
     * @param  User  $user  User to delete
     * @return void
     */
    public function delete(User $user)
    {
        $user->delete();
    }

    /**
     * Toggle user active status
     *
     * @param  User  $user  User to toggle
     * @return void
     */
    public function toggleStatus(User $user)
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
     * Get available roles for current user based on permissions
     *
     * @param  User  $currentUser  Current authenticated user
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableRoles(User $currentUser)
    {
        return $currentUser->hasRole('super_admin')
            ? Role::pluck('name')
            : Role::whereNotIn('name', ['admin', 'super_admin'])->pluck('name');
    }

    /**
     * Check if user can modify target user (admin permission check)
     *
     * @param  User  $currentUser  Current authenticated user
     * @param  User  $targetUser  Target user to check
     */
    public function canModifyUser(User $currentUser, User $targetUser): bool
    {
        if ($targetUser->hasRole(['admin', 'super_admin'])) {
            return $currentUser->hasRole('super_admin');
        }

        return true;
    }

    /**
     * Check if user is super admin
     *
     * @param  User  $currentUser  Current authenticated user
     */
    public function isSuperAdmin(User $currentUser): bool
    {
        return $currentUser->hasRole('super_admin');
    }

    /**
     * Check if user can force delete users and if target is not self
     *
     * @param  User  $currentUser  Current authenticated user
     * @param  int  $targetUserId  Target user ID to check
     */
    public function canForceDeleteUser(User $currentUser, int $targetUserId): bool
    {
        if ($targetUserId === $currentUser->id) {
            return false;
        }

        return $currentUser->can('force delete users');
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
     * Check if user is a student
     *
     * @param  User  $user  User to check
     */
    public function isStudent(User $user): bool
    {
        return $user->hasRole('student');
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
