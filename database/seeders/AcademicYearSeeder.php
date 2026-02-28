<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Seed academic years starting with 2025/2026 as current year.
     */
    public function run(): void
    {
        AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
            'description' => 'Current academic year',
        ]);

        $this->command->info('✓ Academic Year 2025/2026 created (current)');
    }
}
