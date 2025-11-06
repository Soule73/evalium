<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Services\Admin\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class RolePermissionServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleService::class);
    }

    #[Test]
    public function it_can_get_roles_with_permissions(): void
    {
        Role::create(['name' => 'test_role_1']);
        Role::create(['name' => 'test_role_2']);

        $permission = Permission::create(['name' => 'test.permission']);
        Role::findByName('test_role_1')->givePermissionTo($permission);

        $roles = $this->service->getRolesWithPermissions();

        $this->assertCount(2, $roles);
        $this->assertTrue($roles->first()->relationLoaded('permissions'));
        $this->assertArrayHasKey('permissions_count', $roles->first()->toArray());
    }

    #[Test]
    public function it_can_get_all_permissions(): void
    {
        Permission::create(['name' => 'permission.one']);
        Permission::create(['name' => 'permission.two']);
        Permission::create(['name' => 'permission.three']);

        $permissions = $this->service->getAllPermissions();

        $this->assertCount(3, $permissions);
        $this->assertEquals('permission.one', $permissions->first()->name);
    }

    #[Test]
    public function it_can_group_permissions_by_category(): void
    {
        $permissions = collect([
            Permission::create(['name' => 'user.create']),
            Permission::create(['name' => 'user.edit']),
            Permission::create(['name' => 'exam.create']),
            Permission::create(['name' => 'role.create']),
            Permission::create(['name' => 'group.view']),
        ]);

        $grouped = $this->service->groupPermissionsByCategory($permissions);

        $this->assertArrayHasKey(__('permissions.category_users'), $grouped);
        $this->assertArrayHasKey(__('permissions.category_exams'), $grouped);
        $this->assertArrayHasKey(__('permissions.category_roles_permissions'), $grouped);
        $this->assertArrayHasKey(__('permissions.category_groups'), $grouped);

        $this->assertCount(2, $grouped[__('permissions.category_users')]);
        $this->assertCount(1, $grouped[__('permissions.category_exams')]);
        $this->assertCount(1, $grouped[__('permissions.category_roles_permissions')]);
    }

    #[Test]
    public function it_filters_empty_categories_when_grouping(): void
    {
        $permissions = collect([
            Permission::create(['name' => 'user.create']),
        ]);

        $grouped = $this->service->groupPermissionsByCategory($permissions);

        $this->assertArrayHasKey(__('permissions.category_users'), $grouped);
        $this->assertArrayNotHasKey(__('permissions.category_exams'), $grouped);
        $this->assertArrayNotHasKey(__('permissions.category_groups'), $grouped);
    }

    #[Test]
    public function it_can_create_role_without_permissions(): void
    {
        $data = ['name' => 'new_role'];

        $role = $this->service->createRole($data);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('new_role', $role->name);
        $this->assertCount(0, $role->permissions);
        $this->assertDatabaseHas('roles', ['name' => 'new_role']);
    }

    #[Test]
    public function it_can_create_role_with_permissions(): void
    {
        $permission1 = Permission::create(['name' => 'test.permission.one']);
        $permission2 = Permission::create(['name' => 'test.permission.two']);

        $data = [
            'name' => 'role_with_perms',
            'permissions' => [$permission1->id, $permission2->id],
        ];

        $role = $this->service->createRole($data);

        $this->assertEquals('role_with_perms', $role->name);
        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->hasPermissionTo('test.permission.one'));
        $this->assertTrue($role->hasPermissionTo('test.permission.two'));
    }

    #[Test]
    public function it_can_update_role_name(): void
    {
        $role = Role::create(['name' => 'old_name']);

        $updated = $this->service->updateRole($role, ['name' => 'new_name']);

        $this->assertEquals('new_name', $updated->name);
        $this->assertDatabaseHas('roles', ['name' => 'new_name']);
        $this->assertDatabaseMissing('roles', ['name' => 'old_name']);
    }

    #[Test]
    public function it_cannot_rename_system_roles(): void
    {
        $role = Role::create(['name' => 'admin']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.role_cannot_rename_system'));

        $this->service->updateRole($role, ['name' => 'different_name']);
    }

    #[Test]
    public function it_can_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'test_role']);
        $perm1 = Permission::create(['name' => 'perm.one']);
        $perm2 = Permission::create(['name' => 'perm.two']);
        $perm3 = Permission::create(['name' => 'perm.three']);

        $role->givePermissionTo($perm1);

        $updated = $this->service->updateRole($role, [
            'permissions' => [$perm2->id, $perm3->id],
        ]);

        $this->assertCount(2, $updated->permissions);
        $this->assertFalse($updated->hasPermissionTo('perm.one'));
        $this->assertTrue($updated->hasPermissionTo('perm.two'));
        $this->assertTrue($updated->hasPermissionTo('perm.three'));
    }

    #[Test]
    public function it_can_sync_role_permissions(): void
    {
        $role = Role::create(['name' => 'test_role']);
        $perm1 = Permission::create(['name' => 'sync.one']);
        $perm2 = Permission::create(['name' => 'sync.two']);

        $synced = $this->service->syncRolePermissions($role, [$perm1->id, $perm2->id]);

        $this->assertCount(2, $synced->permissions);
        $this->assertTrue($synced->hasPermissionTo('sync.one'));
        $this->assertTrue($synced->hasPermissionTo('sync.two'));
    }

    #[Test]
    public function it_cannot_delete_system_roles(): void
    {
        $systemRoles = ['super_admin', 'admin', 'teacher', 'student'];

        foreach ($systemRoles as $roleName) {
            $role = Role::create(['name' => $roleName]);

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage(__('messages.role_cannot_delete_system'));

            $this->service->deleteRole($role);
        }
    }

    #[Test]
    public function it_cannot_delete_role_assigned_to_users(): void
    {
        $assignedRole = Role::create(['name' => 'assigned_role']);
        $studentRole = Role::create(['name' => 'student']);

        $user = $this->createStudent();
        $user->assignRole($assignedRole);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.role_cannot_delete_assigned'));

        $this->service->deleteRole($assignedRole);

        $this->assertDatabaseHas('roles', ['name' => 'assigned_role']);
    }

    #[Test]
    public function it_can_delete_role_without_users(): void
    {
        $role = Role::create(['name' => 'deletable_role']);

        $result = $this->service->deleteRole($role);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['name' => 'deletable_role']);
    }

    #[Test]
    public function it_can_create_permission(): void
    {
        $data = ['name' => 'new.permission'];

        $permission = $this->service->createPermission($data);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('new.permission', $permission->name);
        $this->assertDatabaseHas('permissions', ['name' => 'new.permission']);
    }

    #[Test]
    public function it_cannot_delete_permission_assigned_to_roles(): void
    {
        $permission = Permission::create(['name' => 'assigned.permission']);
        $role = Role::create(['name' => 'test_role']);
        $role->givePermissionTo($permission);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.permission_cannot_delete_assigned'));

        $this->service->deletePermission($permission);

        $this->assertDatabaseHas('permissions', ['name' => 'assigned.permission']);
    }

    #[Test]
    public function it_can_delete_permission_without_roles(): void
    {
        $permission = Permission::create(['name' => 'deletable.permission']);

        $result = $this->service->deletePermission($permission);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('permissions', ['name' => 'deletable.permission']);
    }

    #[Test]
    public function it_categorizes_user_related_permissions_correctly(): void
    {
        $permissions = collect([
            Permission::create(['name' => 'user.create']),
            Permission::create(['name' => 'student.view']),
            Permission::create(['name' => 'teacher.edit']),
            Permission::create(['name' => 'admin.delete']),
        ]);

        $grouped = $this->service->groupPermissionsByCategory($permissions);

        $this->assertArrayHasKey(__('permissions.category_users'), $grouped);
        $this->assertCount(4, $grouped[__('permissions.category_users')]);
    }

    #[Test]
    public function it_categorizes_mixed_permissions_correctly(): void
    {
        $permissions = collect([
            Permission::create(['name' => 'exam.create']),
            Permission::create(['name' => 'group.delete']),
            Permission::create(['name' => 'level.manage']),
        ]);

        $grouped = $this->service->groupPermissionsByCategory($permissions);

        $this->assertCount(3, $grouped);
        $this->assertArrayHasKey(__('permissions.category_exams'), $grouped);
        $this->assertArrayHasKey(__('permissions.category_groups'), $grouped);
        $this->assertArrayHasKey(__('permissions.category_levels'), $grouped);
    }
}
