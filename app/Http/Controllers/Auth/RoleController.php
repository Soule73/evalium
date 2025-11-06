<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Traits\HasFlashMessages;
use App\Services\Admin\RoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * Display a listing of roles.
     *
     * @return Response The response containing the list of roles.
     */
    public function index(): Response
    {
        Auth::user()->can('view roles');

        $roles = $this->roleService->getRolesWithPermissions();

        $allPermissions = $this->roleService->getAllPermissions();

        $groupedPermissions = $this->roleService->groupPermissionsByCategory($allPermissions);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Show the form for creating a new role.
     *
     * @return Response The response containing the creation form view.
     */
    public function create(): Response
    {
        Auth::user()->can('create roles');

        $permissions = $this->roleService->getAllPermissions();

        $groupedPermissions = $this->roleService->groupPermissionsByCategory($permissions);

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  StoreRoleRequest  $request  The validated request instance containing role data.
     * @return RedirectResponse Redirect response after storing the role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->createRole($request->validated());

        return $this->redirectWithSuccess(route('roles.index'), __('messages.role_created'));
    }

    /**
     * Show the form for editing the specified role.
     *
     * @param  Role  $role  The role instance to be edited.
     * @return Response The response containing the edit form view.
     */
    public function edit(Role $role): Response
    {
        Auth::user()->can('update roles');

        $roleWithPermissions = $this->roleService->loadRolesWithPermissions($role);

        $allPermissions = $this->roleService->getAllPermissions();

        $groupedPermissions = $this->roleService->groupPermissionsByCategory($allPermissions);

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $roleWithPermissions,
            'allPermissions' => $allPermissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Update the specified role in storage.
     *
     * @param  UpdateRoleRequest  $request  The validated request instance containing role data.
     * @param  Role  $role  The role instance to be updated.
     * @return RedirectResponse Redirect response after updating the role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {

            $this->roleService->updateRole($role, $request->validated());

            return $this->redirectWithSuccess('roles.index', __('messages.role_updated'));
        } catch (\Exception $e) {

            Log::error('Failed to update role', $e->getMessage());

            return $this->flashError(__('messages.role_update_failed'));
        }
    }

    /**
     * Sync permissions for a role.
     *
     * @param  SyncRolePermissionsRequest  $request  The validated request containing permissions to sync.
     * @param  Role  $role  The role instance to be updated.
     * @return RedirectResponse Redirect response after syncing role permissions.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        $this->roleService->syncRolePermissions($role, $data['permissions']);

        return $this->redirectWithSuccess('roles.index', __('messages.permissions_updated'));
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  Role  $role  The role instance to be deleted.
     * @return RedirectResponse Redirect response after deleting the role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        Auth::user()->can('delete roles');

        try {
            $this->roleService->deleteRole($role);

            return $this->redirectWithSuccess('roles.index', __('messages.role_deleted'));
        } catch (\Exception $e) {
            Log::error('Failed to delete role', $e->getMessage());

            return $this->flashError(__('messages.role_delete_failed'));
        }
    }
}
