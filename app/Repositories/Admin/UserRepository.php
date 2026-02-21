<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * User Query Service - Handle all read operations for users.
 *
 * Follows Single Responsibility Principle by separating query concerns
 * from business logic in UserManagementService.
 */
class UserRepository implements UserRepositoryInterface
{
    use Paginatable;

    /**
     * Get paginated list of users with filtering.
     *
     * @param  array  $filters  Filter criteria (role, status, search, exclude_roles, include_deleted)
     * @param  int  $perPage  Number of items per page
     * @param  User  $currentUser  Current authenticated user
     */
    public function getUserWithPagination(array $filters, int $perPage, User $currentUser): LengthAwarePaginator
    {
        $query = User::with('roles')->whereNot('id', $currentUser->id);

        if (! empty($filters['include_roles'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['include_roles']);
            });
        }

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
     * Get available roles for current user based on permissions.
     *
     * @param  User  $currentUser  Current authenticated user
     */
    public function getAvailableRoles(User $currentUser): Collection
    {
        return $currentUser->hasRole('super_admin')
            ? Role::pluck('name')
            : Role::whereNotIn('name', ['admin', 'super_admin'])->pluck('name');
    }
}
