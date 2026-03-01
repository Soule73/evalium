<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Level;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Seed 6 classes (2 per level) for the current academic year.
     */
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (! $currentYear) {
            $this->command->error('No current academic year found.');

            return;
        }

        $levels = Level::orderBy('order')->get();

        if ($levels->count() < 3) {
            $this->command->error('Need at least 3 levels (L1, L2, M1).');

            return;
        }

        $groups = ['A', 'B'];
        $maxStudents = ['L1' => 30, 'L2' => 25, 'M1' => 20];

        $count = 0;
        foreach ($levels as $level) {
            foreach ($groups as $group) {
                ClassModel::create([
                    'academic_year_id' => $currentYear->id,
                    'level_id' => $level->id,
                    'name' => $group,
                    'description' => "{$level->name} - Groupe {$group}",
                    'max_students' => $maxStudents[$level->name] ?? 25,
                ]);
                $count++;
            }
        }

        $this->command->info("{$count} Classes created for {$currentYear->name}");
    }
}
