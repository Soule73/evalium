<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Seed subjects for each level.
     */
    public function run(): void
    {
        $levels = Level::all();

        if ($levels->isEmpty()) {
            $this->command->error('✗ No levels found. Ensure levels exist before running this seeder.');
            return;
        }

        $subjectsData = [
            ['name' => 'Mathématiques', 'code' => 'MATH'],
            ['name' => 'Physique', 'code' => 'PHYS'],
            ['name' => 'Chimie', 'code' => 'CHEM'],
            ['name' => 'Informatique', 'code' => 'INFO'],
            ['name' => 'Français', 'code' => 'FRAN'],
            ['name' => 'Anglais', 'code' => 'ANGL'],
            ['name' => 'Histoire', 'code' => 'HIST'],
            ['name' => 'Géographie', 'code' => 'GEOG'],
        ];

        $count = 0;
        foreach ($levels as $level) {
            foreach ($subjectsData as $subjectData) {
                Subject::firstOrCreate(
                    [
                        'level_id' => $level->id,
                        'name' => $subjectData['name'],
                    ],
                    [
                        'code' => $subjectData['code'] . '_' . strtoupper($level->code ?? $level->name),
                        'description' => $subjectData['name'] . ' pour ' . $level->name,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("✓ {$count} Subjects created across " . $levels->count() . ' levels');
    }
}
