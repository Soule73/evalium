<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for UserPolicy authorization rules.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;

        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'delete users']);
        Permission::create(['name' => 'force delete users']);
    }

    /**
     * @test
     */
    public function super_admin_can_update_admin_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->update($superAdmin, $admin));
    }

    /**
     * @test
     */
    public function super_admin_can_update_another_super_admin(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->assertTrue($this->policy->update($superAdmin1, $superAdmin2));
    }

    /**
     * @test
     */
    public function admin_cannot_update_admin_user(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $this->assertFalse($this->policy->update($admin1, $admin2));
    }

    /**
     * @test
     */
    public function admin_cannot_update_super_admin_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->update($admin, $superAdmin));
    }

    /**
     * @test
     */
    public function user_can_update_self(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertTrue($this->policy->update($user, $user));
    }

    /**
     * @test
     */
    public function user_with_permission_can_update_student(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $teacher->givePermissionTo('update users');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertTrue($this->policy->update($teacher, $student));
    }

    /**
     * @test
     */
    public function user_without_permission_cannot_update_other_user(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertFalse($this->policy->update($teacher, $student));
    }

    /**
     * @test
     */
    public function super_admin_can_delete_admin_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->delete($superAdmin, $admin));
    }

    /**
     * @test
     */
    public function admin_cannot_delete_admin_user(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $this->assertFalse($this->policy->delete($admin1, $admin2));
    }

    /**
     * @test
     */
    public function admin_cannot_delete_super_admin_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->delete($admin, $superAdmin));
    }

    /**
     * @test
     */
    public function user_cannot_delete_self(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertFalse($this->policy->delete($user, $user));
    }

    /**
     * @test
     */
    public function user_with_permission_can_delete_student(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $teacher->givePermissionTo('delete users');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertTrue($this->policy->delete($teacher, $student));
    }

    /**
     * @test
     */
    public function super_admin_can_toggle_admin_status(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->toggleStatus($superAdmin, $admin));
    }

    /**
     * @test
     */
    public function admin_cannot_toggle_admin_status(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $this->assertFalse($this->policy->toggleStatus($admin1, $admin2));
    }

    /**
     * @test
     */
    public function admin_cannot_toggle_super_admin_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->toggleStatus($admin, $superAdmin));
    }

    /**
     * @test
     */
    public function user_cannot_toggle_own_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertFalse($this->policy->toggleStatus($user, $user));
    }

    /**
     * @test
     */
    public function super_admin_can_force_delete_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->forceDelete($superAdmin, $admin));
    }

    /**
     * @test
     */
    public function admin_cannot_force_delete_admin(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $this->assertFalse($this->policy->forceDelete($admin1, $admin2));
    }

    /**
     * @test
     */
    public function admin_cannot_force_delete_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->forceDelete($admin, $superAdmin));
    }

    /**
     * @test
     */
    public function user_cannot_force_delete_self(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertFalse($this->policy->forceDelete($user, $user));
    }

    /**
     * @test
     */
    public function user_with_permission_can_force_delete_student(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $teacher->givePermissionTo('force delete users');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertTrue($this->policy->forceDelete($teacher, $student));
    }
}
