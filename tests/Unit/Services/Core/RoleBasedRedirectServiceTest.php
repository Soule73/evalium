<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Core;

use App\Models\User;
use App\Services\Core\RoleBasedRedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleBasedRedirectServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleBasedRedirectService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoleBasedRedirectService;

        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);
    }

    public function test_get_dashboard_type_returns_admin_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $type = $this->service->getDashboardType($user);

        $this->assertEquals('admin', $type);
    }

    public function test_get_dashboard_type_returns_admin_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $type = $this->service->getDashboardType($user);

        $this->assertEquals('admin', $type);
    }

    public function test_get_dashboard_type_returns_teacher(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $type = $this->service->getDashboardType($user);

        $this->assertEquals('teacher', $type);
    }

    public function test_get_dashboard_type_returns_student(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $type = $this->service->getDashboardType($user);

        $this->assertEquals('student', $type);
    }

    public function test_get_dashboard_type_returns_null_for_no_role(): void
    {
        $user = User::factory()->create();

        $type = $this->service->getDashboardType($user);

        $this->assertNull($type);
    }

    public function test_get_dashboard_type_returns_null_for_null_user(): void
    {
        $type = $this->service->getDashboardType(null);

        $this->assertNull($type);
    }

    public function test_get_dashboard_route_returns_admin_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $route = $this->service->getDashboardRoute($user);

        $this->assertEquals(route('dashboard'), $route);
    }

    public function test_get_dashboard_route_returns_teacher_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $route = $this->service->getDashboardRoute($user);

        $this->assertEquals(route('teacher.dashboard'), $route);
    }

    public function test_get_dashboard_route_returns_student_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $route = $this->service->getDashboardRoute($user);

        $this->assertEquals(route('dashboard'), $route);
    }

    public function test_get_dashboard_route_throws_exception_for_no_role(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->service->getDashboardRoute($user);
    }

    public function test_is_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($this->service->isAdmin($user));
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($this->service->isAdmin($user));
    }

    public function test_is_admin_returns_false_for_non_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertFalse($this->service->isAdmin($user));
    }

    public function test_is_teacher_returns_true(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertTrue($this->service->isTeacher($user));
    }

    public function test_is_teacher_returns_false_for_non_teacher(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->assertFalse($this->service->isTeacher($user));
    }

    public function test_is_student_returns_true(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->assertTrue($this->service->isStudent($user));
    }

    public function test_is_student_returns_false_for_non_student(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertFalse($this->service->isStudent($user));
    }

    public function test_uses_authenticated_user_when_no_user_provided(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user);

        $type = $this->service->getDashboardType();

        $this->assertEquals('teacher', $type);
    }
}
