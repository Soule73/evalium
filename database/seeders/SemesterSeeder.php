<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Seed semesters for the current academic year.
     */
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (!$currentYear) {
            $this->command->error('✗ No current academic year found. Run AcademicYearSeeder first.');
            return;
        }

        Semester::create([
            'academic_year_id' => $currentYear->id,
            'name' => 'Semestre 1',
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
            'order_number' => 1,
        ]);

        Semester::create([
            'academic_year_id' => $currentYear->id,
            'name' => 'Semestre 2',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'order_number' => 2,
        ]);

        $this->command->info('✓ 2 Semesters created for ' . $currentYear->name);
    }
}
