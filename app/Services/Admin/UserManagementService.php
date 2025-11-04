<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Group;
use App\Services\Admin\GroupService;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * User Management Service - Handle user CRUD operations and role assignments
 * 
 * Single Responsibility: Manage user lifecycle and role/group assignments
 * Dependencies: GroupService for student group assignments
 */
class UserManagementService
{
    public function __construct(
        private readonly GroupService $groupService
    ) {}

    /**
     * Get paginated list of users with filtering
     *
     * @param array $filters Filter criteria (role, status, search, exclude_roles, include_deleted)
     * @param int $perPage Number of items per page
     * @param User $currentUser Current authenticated user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserWithPagination(array $filters, int $perPage, User $currentUser)
    {
        $query = User::with('roles')->whereNot('id', $currentUser->id);

        if (!empty($filters['exclude_roles'])) {
            $query->whereDoesntHave('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['exclude_roles']);
            });
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (!empty($filters['include_deleted'])) {
            $query->withTrashed();
        }

        $per_page = $filters['per_page'] ?? 10;

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($per_page)->withQueryString();

        return $users;
    }

    /**
     * Create a new user with random password and send credentials notification
     *
     * @param array $data User data (name, email, role, group_id optional)
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

            if ($data['role'] === 'student' && isset($data['group_id'])) {
                $group = Group::findOrFail($data['group_id']);
                $this->groupService->assignStudentToGroup($group, $user->id);
            }

            $user->notify(new UserCredentialsNotification($password, $data['role']));

            return $user;
        });
    }

    /**
     * Update existing user data and role
     *
     * @param User $user User to update
     * @param array $data Updated data (name, email, password optional, role, group_id optional)
     * @return void
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

                if (!isset($data['role']) || !Role::where('name', $data['role'])->exists()) {
                    throw new \InvalidArgumentException("Role is required for update.");
                }

                $user->syncRoles([$data['role']]);

                if ($data['role'] === 'student' && isset($data['group_id'])) {
                    $group = Group::findOrFail($data['group_id']);
                    $this->groupService->assignStudentToGroup($group, $user->id);
                }
            });
        } catch (\Exception $e) {
            Log::error("Error updating user: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete a user
     *
     * @param User $user User to delete
     * @return void
     */
    public function delete(User $user)
    {
        $user->delete();
    }

    /**
     * Toggle user active status
     *
     * @param User $user User to toggle
     * @return void
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
    }

    /**
     * Change student's group assignment
     *
     * @param User $student Student to reassign
     * @param int $newGroupId New group ID
     * @return void
     * @throws \InvalidArgumentException
     */
    public function changeStudentGroup(User $student, int $newGroupId)
    {
        if (!$student->hasRole('student')) {
            throw new \InvalidArgumentException("User must be a student.");
        }

        $newGroup = Group::findOrFail($newGroupId);
        $this->groupService->assignStudentToGroup($newGroup, $student->id);
    }
}
