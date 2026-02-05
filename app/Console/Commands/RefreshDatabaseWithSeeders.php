<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshDatabaseWithSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh-all {--force : Force the operation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the database and run all seeders (migrate:fresh + db:seed)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Vérifier si en production
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Application is in production!');
            $this->error('Use --force to run this command in production.');

            return Command::FAILURE;
        }

        $this->warn('This will DROP ALL TABLES and re-create them!');

        if (! $this->confirm('Do you really wish to continue?', false)) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Starting database refresh...');
        $this->newLine();

        // 1. Drop all tables and re-migrate
        $this->info('Step 1/2: Running migrations (fresh)...');
        Artisan::call('migrate:fresh', [], $this->getOutput());
        $this->info('Migrations completed!');
        $this->newLine();

        // 2. Run all seeders
        $this->info('Step 2/2: Running seeders...');
        Artisan::call('db:seed', [], $this->getOutput());
        $this->info('Seeders completed!');
        $this->newLine();

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Database refresh completed successfully!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->table(
            ['Component', 'Status'],
            [
                ['Migrations', 'Done'],
                ['Roles & Permissions', 'Done'],
                ['Levels', 'Done'],
                ['Users', 'Done'],
                ['ClasseModel', 'Done'],
                ['Assessments', 'Done'],
                ['Assignments', 'Done'],
            ]
        );

        $this->newLine();
        $this->info('You can now login with the seeded users.');

        return Command::SUCCESS;
    }
}
