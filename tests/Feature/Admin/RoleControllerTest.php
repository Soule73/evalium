<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class RoleControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private Role $editableRole;

    private Role $lockedRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->editableRole = Role::where('name', 'admin')->firstOrFail();
        $this->lockedRole = Role::where('name', 'teacher')->firstOrFail();
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $this->actingAs($this->student)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_teacher_cannot_access_index(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Roles/Index')
                    ->has('roles')
                    ->has('groupedPermissions')
                    ->has('filters')
            );
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_edit(): void
    {
        $this->get(route('admin.roles.edit', $this->editableRole))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_edit(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.roles.edit', $this->editableRole))
            ->assertForbidden();
    }

    public function test_teacher_cannot_access_edit(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.roles.edit', $this->editableRole))
            ->assertForbidden();
    }

    public function test_admin_can_access_edit_of_editable_role(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.edit', $this->editableRole))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Roles/Edit')
                    ->has('role')
                    ->has('groupedPermissions')
                    ->where('role.is_editable', true)
            );
    }

    public function test_admin_can_access_edit_of_locked_role(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.edit', $this->lockedRole))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Roles/Edit')
                    ->has('role')
                    ->where('role.is_editable', false)
            );
    }

    // ---------------------------------------------------------------
    // Sync Permissions
    // ---------------------------------------------------------------

    public function test_guest_cannot_sync_permissions(): void
    {
        $permission = Permission::first();

        $this->post(route('admin.roles.sync-permissions', $this->editableRole), [
            'permissions' => [$permission->id],
        ])->assertRedirect(route('login'));
    }

    public function test_student_cannot_sync_permissions(): void
    {
        $permission = Permission::first();

        $this->actingAs($this->student)
            ->post(route('admin.roles.sync-permissions', $this->editableRole), [
                'permissions' => [$permission->id],
            ])
            ->assertForbidden();
    }

    public function test_teacher_cannot_sync_permissions(): void
    {
        $permission = Permission::first();

        $this->actingAs($this->teacher)
            ->post(route('admin.roles.sync-permissions', $this->editableRole), [
                'permissions' => [$permission->id],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_sync_permissions_on_editable_role(): void
    {
        $permissions = Permission::take(3)->pluck('id')->toArray();

        $this->actingAs($this->admin)
            ->post(route('admin.roles.sync-permissions', $this->editableRole), [
                'permissions' => $permissions,
            ])
            ->assertRedirect(route('admin.roles.index'));

        foreach ($permissions as $permissionId) {
            $this->assertTrue(
                $this->editableRole->fresh()->permissions->contains('id', $permissionId)
            );
        }
    }

    public function test_admin_cannot_sync_permissions_on_locked_role(): void
    {
        $originalPermissions = $this->lockedRole->permissions->pluck('id')->sort()->values()->toArray();

        $this->actingAs($this->admin)
            ->post(route('admin.roles.sync-permissions', $this->lockedRole), [
                'permissions' => [Permission::first()->id],
            ])
            ->assertForbidden();

        $this->assertEquals(
            $originalPermissions,
            $this->lockedRole->fresh()->permissions->pluck('id')->sort()->values()->toArray()
        );
    }

    public function test_all_locked_roles_cannot_be_synced(): void
    {
        $lockedRoleNames = ['super_admin', 'teacher', 'student'];

        foreach ($lockedRoleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $originalPermissions = $role->permissions->pluck('id')->sort()->values()->toArray();

            $this->actingAs($this->admin)
                ->post(route('admin.roles.sync-permissions', $role), [
                    'permissions' => [Permission::first()->id],
                ])
                ->assertForbidden();

            $this->assertEquals(
                $originalPermissions,
                $role->fresh()->permissions->pluck('id')->sort()->values()->toArray(),
                "Permissions of locked role '{$roleName}' should not have changed"
            );
        }
    }

    public function test_sync_replaces_existing_permissions(): void
    {
        $customRole = Role::create(['name' => 'custom_test_role']);
        $allPermissions = Permission::take(5)->get();
        $initial = $allPermissions->take(3)->pluck('id')->toArray();
        $replacement = $allPermissions->skip(3)->pluck('id')->toArray();

        $customRole->syncPermissions($initial);

        $this->actingAs($this->admin)
            ->post(route('admin.roles.sync-permissions', $customRole), [
                'permissions' => $replacement,
            ])
            ->assertRedirect(route('admin.roles.index'));

        $fresh = $customRole->fresh()->load('permissions');

        foreach ($replacement as $id) {
            $this->assertTrue($fresh->permissions->contains('id', $id));
        }

        foreach ($initial as $id) {
            $this->assertFalse($fresh->permissions->contains('id', $id));
        }
    }

    public function test_sync_requires_permissions_array(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.sync-permissions', $this->editableRole), [])
            ->assertSessionHasErrors('permissions');
    }

    public function test_sync_rejects_invalid_permission_ids(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.sync-permissions', $this->editableRole), [
                'permissions' => [99999],
            ])
            ->assertSessionHasErrors('permissions.0');
    }
}
