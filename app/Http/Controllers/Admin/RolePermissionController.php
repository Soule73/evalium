<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): Response
    {
        $roles = Role::with('permissions')
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Le rôle a été créé avec succès.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): Response
    {
        $role->load('permissions');
        $allPermissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        // Empêcher la modification des rôles système
        if (in_array($role->name, ['super_admin', 'admin', 'teacher', 'student'])) {
            return back()->with('error', 'Les rôles système ne peuvent pas être renommés.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Le rôle a été modifié avec succès.');
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($validated['permissions']);

        return back()->with('success', 'Les permissions ont été mises à jour avec succès.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Empêcher la suppression des rôles système
        if (in_array($role->name, ['super_admin', 'admin', 'teacher', 'student'])) {
            return back()->with('error', 'Les rôles système ne peuvent pas être supprimés.');
        }

        // Vérifier si le rôle est assigné à des utilisateurs
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer ce rôle car il est assigné à des utilisateurs.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Le rôle a été supprimé avec succès.');
    }

    /**
     * Display a listing of permissions.
     */
    public function permissionsIndex(): Response
    {
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created permission in storage.
     */
    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ], [
            'name.required' => 'Le nom de la permission est obligatoire.',
            'name.unique' => 'Cette permission existe déjà.',
        ]);

        Permission::create(['name' => $validated['name']]);

        return back()->with('success', 'La permission a été créée avec succès.');
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroyPermission(Permission $permission): RedirectResponse
    {
        // Vérifier si la permission est assignée à des rôles
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer cette permission car elle est assignée à des rôles.');
        }

        $permission->delete();

        return back()->with('success', 'La permission a été supprimée avec succès.');
    }
}
