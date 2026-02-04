<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Level;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Seed classes for the current academic year.
     */
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (! $currentYear) {
            $this->command->error('✗ No current academic year found.');

            return;
        }

        $l1Level = Level::where('name', 'L1')->first();
        $m1Level = Level::where('name', 'M1')->first();

        if (! $l1Level || ! $m1Level) {
            $this->command->error('✗ L1 or M1 level not found.');

            return;
        }

        ClassModel::create([
            'academic_year_id' => $currentYear->id,
            'level_id' => $l1Level->id,
            'name' => 'A',
            'description' => 'L1 - Groupe A',
            'max_students' => 30,
        ]);

        ClassModel::create([
            'academic_year_id' => $currentYear->id,
            'level_id' => $m1Level->id,
            'name' => 'A',
            'description' => 'M1 - Groupe A',
            'max_students' => 30,
        ]);

        $this->command->info("✓ 2 Classes created: L1-A, M1-A for {$currentYear->name}");
    }
}
