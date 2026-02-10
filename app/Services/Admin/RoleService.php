<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Services\Traits\Paginatable;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role and Permission Management Service
 *
 * Responsibilities:
 * - CRUD operations for roles (create, read, update, delete)
 * - CRUD operations for permissions (create, read, delete)
 * - Permission synchronization to roles
 * - Permission grouping by business category
 * - Deletion validation (system role protection)
 */
class RoleService
{
    use Paginatable;

    private const SYSTEM_ROLES = ['super_admin', 'admin', 'teacher', 'student'];

    private const CATEGORY_MAPPINGS = [
        'category_users' => ['user', 'student', 'teacher', 'admin'],
        'category_assessments' => ['assessment', 'result'],
        'category_class_subjects' => ['class subject'],
        'category_classes' => ['class'],
        'category_enrollments' => ['enrollment'],
        'category_levels' => ['level'],
        'category_subjects' => ['subject'],
        'category_academic_years' => ['academic year'],
        'category_roles_permissions' => ['role', 'permission'],
    ];

    /**
     * Load a role with its permissions
     */
    public function loadRolesWithPermissions(Role $role): Role
    {
        return $role->load('permissions');
    }

    /**
     * Retrieve all roles with their permissions
     */
    public function getRolesWithPermissions(): Collection
    {
        return Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    /**
     * Retrieve paginated roles with their permissions
     *
     * @param  int  $perPage  Number of items per page
     * @param  string|null  $search  Search term for role name
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRolesWithPermissionsPaginated(int $perPage = 15, ?string $search = null)
    {
        $query = Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $this->simplePaginate($query, $perPage);
    }

    /**
     * Retrieve all permissions
     */
    public function getAllPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * Permissions by business category
     *
     * @return array<string, array<Permission>>
     */
    public function groupPermissionsByCategory(Collection $permissions): array
    {

        $grouped = [];

        foreach (array_keys(self::CATEGORY_MAPPINGS) as $categoryKey) {
            $grouped[__("permissions.{$categoryKey}")] = [];
        }

        foreach ($permissions as $permission) {
            $categoryKey = $this->determineCategoryForPermission($permission->name);

            if ($categoryKey) {
                $translatedCategory = __("permissions.{$categoryKey}");
                $grouped[$translatedCategory][] = $permission;
            }
        }

        return array_filter($grouped, fn ($items) => ! empty($items));
    }

    /**
     * Create a new role with optional permissions
     *
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role
    {
        $role = Role::create(['name' => $data['name']]);

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update an existing role
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception If attempting to rename a system role
     */
    public function updateRole(Role $role, array $data): Role
    {
        if ($this->isSystemRole($role) && isset($data['name']) && $data['name'] !== $role->name) {
            throw new \Exception(__('messages.role_cannot_rename_system'));
        }

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh(['permissions']);
    }

    /**
     * Synchronize role permissions
     *
     * @param  array<int>  $permissionIds
     */
    public function syncRolePermissions(Role $role, array $permissionIds): Role
    {
        $role->syncPermissions($permissionIds);

        return $role->fresh(['permissions']);
    }

    /**
     * Delete a role
     *
     * @throws \Exception If system role or assigned to users
     */
    public function deleteRole(Role $role): bool
    {
        if ($this->isSystemRole($role)) {
            throw new \Exception(__('messages.role_cannot_delete_system'));
        }

        if ($role->users()->count() > 0) {
            throw new \Exception(__('messages.role_cannot_delete_assigned'));
        }

        return $role->delete();
    }

    /**
     * Create a new permission
     *
     * @param  array<string, mixed>  $data
     */
    public function createPermission(array $data): Permission
    {
        return Permission::create(['name' => $data['name']]);
    }

    /**
     * Delete a permission
     *
     * @throws \Exception If permission is assigned to roles
     */
    public function deletePermission(Permission $permission): bool
    {
        if ($permission->roles()->count() > 0) {
            throw new \Exception(__('messages.permission_cannot_delete_assigned'));
        }

        return $permission->delete();
    }

    /**
     * Determine the category of a permission based on its name
     */
    private function determineCategoryForPermission(string $permissionName): ?string
    {
        foreach (self::CATEGORY_MAPPINGS as $categoryKey => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($permissionName, $keyword)) {
                    return $categoryKey;
                }
            }
        }

        return null;
    }

    /**
     * Check if a role is a protected system role
     */
    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLES, true);
    }
}
