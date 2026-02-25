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

  private Role $role;

  protected function setUp(): void
  {
    parent::setUp();
    $this->seedRolesAndPermissions();
    config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

    $this->admin = $this->createAdmin();
    $this->teacher = $this->createTeacher();
    $this->student = $this->createStudent();

    $this->role = Role::where('name', 'teacher')->firstOrFail();
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
        fn($page) => $page
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
    $this->get(route('admin.roles.edit', $this->role))->assertRedirect(route('login'));
  }

  public function test_student_cannot_access_edit(): void
  {
    $this->actingAs($this->student)
      ->get(route('admin.roles.edit', $this->role))
      ->assertForbidden();
  }

  public function test_teacher_cannot_access_edit(): void
  {
    $this->actingAs($this->teacher)
      ->get(route('admin.roles.edit', $this->role))
      ->assertForbidden();
  }

  public function test_admin_can_access_edit(): void
  {
    $this->actingAs($this->admin)
      ->get(route('admin.roles.edit', $this->role))
      ->assertOk()
      ->assertInertia(
        fn($page) => $page
          ->component('Admin/Roles/Edit')
          ->has('role')
          ->has('groupedPermissions')
      );
  }

  // ---------------------------------------------------------------
  // Sync Permissions
  // ---------------------------------------------------------------

  public function test_guest_cannot_sync_permissions(): void
  {
    $permission = Permission::first();

    $this->post(route('admin.roles.sync-permissions', $this->role), [
      'permissions' => [$permission->id],
    ])->assertRedirect(route('login'));
  }

  public function test_student_cannot_sync_permissions(): void
  {
    $permission = Permission::first();

    $this->actingAs($this->student)
      ->post(route('admin.roles.sync-permissions', $this->role), [
        'permissions' => [$permission->id],
      ])
      ->assertForbidden();
  }

  public function test_teacher_cannot_sync_permissions(): void
  {
    $permission = Permission::first();

    $this->actingAs($this->teacher)
      ->post(route('admin.roles.sync-permissions', $this->role), [
        'permissions' => [$permission->id],
      ])
      ->assertForbidden();
  }

  public function test_admin_can_sync_permissions(): void
  {
    $permissions = Permission::take(3)->pluck('id')->toArray();

    $this->actingAs($this->admin)
      ->post(route('admin.roles.sync-permissions', $this->role), [
        'permissions' => $permissions,
      ])
      ->assertRedirect(route('admin.roles.index'));

    foreach ($permissions as $permissionId) {
      $this->assertTrue(
        $this->role->fresh()->permissions->contains('id', $permissionId)
      );
    }
  }

  public function test_sync_replaces_existing_permissions(): void
  {
    $allPermissions = Permission::take(5)->get();
    $initial = $allPermissions->take(3)->pluck('id')->toArray();
    $replacement = $allPermissions->skip(3)->pluck('id')->toArray();

    $this->role->syncPermissions($initial);

    $this->actingAs($this->admin)
      ->post(route('admin.roles.sync-permissions', $this->role), [
        'permissions' => $replacement,
      ])
      ->assertRedirect(route('admin.roles.index'));

    $fresh = $this->role->fresh()->load('permissions');

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
      ->post(route('admin.roles.sync-permissions', $this->role), [])
      ->assertSessionHasErrors('permissions');
  }

  public function test_sync_rejects_invalid_permission_ids(): void
  {
    $this->actingAs($this->admin)
      ->post(route('admin.roles.sync-permissions', $this->role), [
        'permissions' => [99999],
      ])
      ->assertSessionHasErrors('permissions.0');
  }
}
