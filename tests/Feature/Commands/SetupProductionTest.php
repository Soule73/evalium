<?php

namespace Tests\Feature\Commands;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupProductionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the setup command with simulated prompts.
     *
     * @param  array<string, mixed>  $overrides  Prompt answers to override defaults
     */
    private function runSetupCommand(array $overrides = []): \Illuminate\Testing\PendingCommand
    {
        $defaults = [
            'name' => 'Admin',
            'email' => 'admin@evalium.test',
            'password' => 'secret1234',
            'confirm' => 'secret1234',
            'year_name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ];

        $data = array_merge($defaults, $overrides);

        return $this->artisan('app:setup-production', ['--force' => true, '--skip-reset' => true])
            ->expectsQuestion('Admin name', $data['name'])
            ->expectsQuestion('Admin email', $data['email'])
            ->expectsQuestion('Password (min 8 characters)', $data['password'])
            ->expectsQuestion('Confirm password', $data['confirm'])
            ->expectsQuestion('Academic year name', $data['year_name'])
            ->expectsQuestion('Start date (YYYY-MM-DD)', $data['start_date'])
            ->expectsQuestion('End date (YYYY-MM-DD)', $data['end_date']);
    }

    public function test_creates_roles_and_permissions(): void
    {
        $this->runSetupCommand()->assertSuccessful();

        $expectedRoles = ['super_admin', 'admin', 'teacher', 'student'];

        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->exists(),
                "Role '{$roleName}' was not created."
            );
        }

        $this->assertGreaterThan(0, Permission::count());
    }

    public function test_creates_super_admin_user(): void
    {
        $this->runSetupCommand([
            'name' => 'Production Admin',
            'email' => 'prod@evalium.test',
            'password' => 'strongpass1',
            'confirm' => 'strongpass1',
        ])->assertSuccessful();

        $admin = User::where('email', 'prod@evalium.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Production Admin', $admin->name);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_creates_academic_year_as_current(): void
    {
        $this->runSetupCommand([
            'year_name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ])->assertSuccessful();

        $year = AcademicYear::where('name', '2026/2027')->first();

        $this->assertNotNull($year);
        $this->assertTrue($year->is_current);
        $this->assertSame('2026-09-01', $year->start_date->format('Y-m-d'));
        $this->assertSame('2027-06-30', $year->end_date->format('Y-m-d'));
    }

    public function test_full_setup_produces_usable_state(): void
    {
        $this->runSetupCommand()->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertSame(1, AcademicYear::count());
        $this->assertGreaterThanOrEqual(4, Role::count());
        $this->assertGreaterThan(0, Permission::count());
    }

    public function test_admin_has_all_permissions(): void
    {
        $this->runSetupCommand()->assertSuccessful();

        $admin = User::first();

        $this->assertTrue($admin->hasAllPermissions(Permission::all()));
    }
}
