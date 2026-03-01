<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Prepares the application for first production deployment.
 *
 * Resets the database, seeds roles and permissions, creates
 * a super-admin account and an initial academic year so the
 * platform is immediately usable after deployment.
 */
class SetupProduction extends Command
{
    protected $signature = 'app:setup-production
                            {--force : Skip the confirmation prompt}
                            {--skip-reset : Skip database reset (for testing)}';

    protected $description = 'Reset the database and set up roles, super-admin account and academic year for production.';

    public function handle(): int
    {
        if (! $this->confirmDangerousOperation()) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        if (! $this->option('skip-reset')) {
            $this->resetDatabase();
        }

        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();
        $this->createAcademicYear();

        $this->newLine();
        $this->info('=== Setup complete ===');
        $this->info("Super-admin: {$admin->email}");
        $this->info('You can now log in and start configuring the platform.');

        return self::SUCCESS;
    }

    /**
     * Ask for explicit confirmation before wiping the database.
     */
    private function confirmDangerousOperation(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $this->warn('This will WIPE the entire database and create a fresh setup.');

        return confirm(
            label: 'Are you sure you want to continue?',
            default: false,
        );
    }

    /**
     * Run migrate:fresh to reset the database schema.
     */
    private function resetDatabase(): void
    {
        $this->info('Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->newLine();
    }

    /**
     * Seed all roles and permissions via the dedicated seeder.
     */
    private function seedRolesAndPermissions(): void
    {
        $this->info('Seeding roles and permissions...');
        $this->call('db:seed', [
            '--class' => RoleAndPermissionSeeder::class,
            '--force' => true,
        ]);
        $this->newLine();
    }

    /**
     * Prompt for credentials and create the super-admin user.
     */
    private function createSuperAdmin(): User
    {
        $this->info('--- Super Admin Account ---');

        $name = text(
            label: 'Admin name',
            default: 'Super Admin',
            required: true,
        );

        $email = text(
            label: 'Admin email',
            required: true,
            validate: function (string $value) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Please enter a valid email address.';
                }

                return null;
            },
        );

        $passwordValue = password(
            label: 'Password (min 8 characters)',
            required: true,
            validate: function (string $value) {
                if (strlen($value) < 8) {
                    return 'Password must be at least 8 characters.';
                }

                return null;
            },
        );

        $confirmation = password(
            label: 'Confirm password',
            required: true,
            validate: function (string $value) use ($passwordValue) {
                if ($value !== $passwordValue) {
                    return 'Passwords do not match.';
                }

                return null;
            },
        );

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($confirmation),
            'is_active' => true,
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('super_admin');

        $this->info("Super-admin \"{$admin->name}\" created successfully.");
        $this->newLine();

        return $admin;
    }

    /**
     * Prompt for academic year details and create the initial current year.
     */
    private function createAcademicYear(): void
    {
        $this->info('--- Academic Year ---');

        $currentYear = (int) date('Y');
        $defaultName = date('n') >= 9
            ? "{$currentYear}/".($currentYear + 1)
            : ($currentYear - 1)."/{$currentYear}";

        $name = text(
            label: 'Academic year name',
            default: $defaultName,
            required: true,
            validate: function (string $value) {
                if (! preg_match('/^\d{4}\/\d{4}$/', $value)) {
                    return 'Format must be YYYY/YYYY (e.g. 2025/2026).';
                }

                return null;
            },
        );

        [$startYear] = explode('/', $name);

        $defaultStart = "{$startYear}-09-01";
        $defaultEnd = ((int) $startYear + 1).'-06-30';

        $startDate = text(
            label: 'Start date (YYYY-MM-DD)',
            default: $defaultStart,
            required: true,
            validate: function (string $value) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return 'Format must be YYYY-MM-DD.';
                }

                return null;
            },
        );

        $endDate = text(
            label: 'End date (YYYY-MM-DD)',
            default: $defaultEnd,
            required: true,
            validate: function (string $value) use ($startDate) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return 'Format must be YYYY-MM-DD.';
                }
                if ($value <= $startDate) {
                    return 'End date must be after start date.';
                }

                return null;
            },
        );

        AcademicYear::create([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => true,
        ]);

        $this->info("Academic year \"{$name}\" created and set as current.");
    }
}
