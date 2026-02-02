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

        if (!$currentYear) {
            $this->command->error('✗ No current academic year found.');
            return;
        }

        $levels = Level::all();

        if ($levels->isEmpty()) {
            $this->command->error('✗ No levels found.');
            return;
        }

        $classNames = ['A', 'B'];
        $count = 0;

        foreach ($levels as $level) {
            foreach ($classNames as $className) {
                ClassModel::firstOrCreate(
                    [
                        'academic_year_id' => $currentYear->id,
                        'level_id' => $level->id,
                        'name' => $className,
                    ],
                    [
                        'description' => $level->name . ' - Groupe ' . $className,
                        'max_students' => 30,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("✓ {$count} Classes created for {$currentYear->name}");
    }
}
