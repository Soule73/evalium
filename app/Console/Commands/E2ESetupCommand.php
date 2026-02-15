<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Command to setup E2E test environment by creating a test database and seeding it.
 * This prepares the application for end-to-end testing by ensuring a clean database state.
 * It creates an SQLite database file specifically for E2E testing, runs migrations,
 * and seeds the database with initial data.
 * Usage: php artisan e2e:setup
 * This command should be run before executing E2E tests to ensure the environment is ready.
 * Ensure that the database connection for E2E testing is correctly configured
 * in the database configuration file before running this command.
 */
class E2ESetupCommand extends Command
{
    protected $signature = 'e2e:setup';

    protected $description = 'Setup database and seed data for E2E tests';

    public function handle(): int
    {
        $this->info('[ArtisanE2ESetup] Setting up E2E test environment...');

        $dbPath = config('database.connections.e2e_testing.database');

        try {
            if (! is_string($dbPath) || $dbPath === '') {
                $this->error('[ArtisanE2ESetup] Invalid E2E database path in configuration.');

                return self::FAILURE;
            }

            $dbDirectory = dirname($dbPath);
            if (! is_dir($dbDirectory)) {
                mkdir($dbDirectory, 0777, true);
            }

            if (file_exists($dbPath)) {
                $this->info("[ArtisanE2ESetup] Removing existing database: {$dbPath}");
                unlink($dbPath);
            }

            $this->info('[ArtisanE2ESetup] Creating fresh SQLite database...');

            touch($dbPath);

            $this->info('[ArtisanE2ESetup] Clearing configuration cache...');

            Artisan::call('config:clear');

            $this->info('[ArtisanE2ESetup] Running migrations on E2E database...');

            Artisan::call('migrate:fresh', [
                '--database' => 'e2e_testing',
                '--force' => true,
            ]);

            $this->info('[ArtisanE2ESetup] Seeding E2E database...');

            Artisan::call('db:seed', [
                '--database' => 'e2e_testing',
                '--force' => true,
            ]);

            $this->info('[ArtisanE2ESetup] complete');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('[ArtisanE2ESetup] Failed to setup E2E environment: ' .

                $e->getMessage());

            return self::FAILURE;
        }
    }
}
