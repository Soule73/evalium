<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Services\Admin\RoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Manages role permission configuration.
 *
 * Note: Roles are predefined (super_admin, admin, teacher, student)
 * and cannot be created or deleted. Only permissions can be configured.
 */
class RoleController extends Controller
{
    use AuthorizesRequests, HandlesIndexRequests;

    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * Display a listing of roles with their permissions.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search']
        );

        $roles = $this->roleService->getRolesWithPermissionsPaginated($perPage, $filters['search'] ?? null);
        $groupedPermissions = $this->roleService->groupPermissionsByCategory(
            $this->roleService->getAllPermissions()
        );

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the permission configuration form for a role.
     */
    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $roleWithPermissions = $this->roleService->loadRolesWithPermissions($role);
        $groupedPermissions = $this->roleService->groupPermissionsByCategory(
            $this->roleService->getAllPermissions()
        );

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $roleWithPermissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $this->roleService->syncRolePermissions($role, $request->validated()['permissions']);

        return redirect()->route('admin.roles.index')->flashSuccess(__('messages.permissions_updated'));
    }
}
