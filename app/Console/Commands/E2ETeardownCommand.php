<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to teardown E2E test environment by removing test database and data.
 * This helps maintain a clean state after E2E tests have been executed.
 * It deletes the SQLite database file used for E2E testing.
 * Usage: php artisan e2e:teardown
 * This command should be run after E2E tests to ensure no residual data remains.
 * It is complementary to the e2e:setup command which initializes the test environment.
 * Ensure that the database connection for E2E testing is correctly configured
 * in the database configuration file before running this command.
 */
class E2ETeardownCommand extends Command
{
    protected $signature = 'e2e:teardown';

    protected $description = 'Clean up E2E test data and database';

    public function handle(): int
    {
        $this->info('Cleaning up E2E test environment...');

        $dbPath = config('database.connections.e2e_testing.database');

        try {
            if (file_exists($dbPath)) {
                $this->info("Removing SQLite database: {$dbPath}");

                unlink($dbPath);

                $this->info('[ArtisanE2ETeardown] complete');
            } else {
                $this->warn('[ArtisanE2ETeardown] No E2E database file found to clean up');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('[ArtisanE2ETeardown] Failed to teardown E2E environment: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
