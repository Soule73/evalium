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
        $l1Level = Level::where('name', 'L1')->first();
        $m1Level = Level::where('name', 'M1')->first();

        if (! $l1Level || ! $m1Level) {
            $this->command->error('✗ L1 or M1 level not found.');

            return;
        }

        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH_L1', 'level_id' => $l1Level->id],
            ['name' => 'Physics', 'code' => 'PHYS_L1', 'level_id' => $l1Level->id],
            ['name' => 'Computer Science', 'code' => 'CS_L1', 'level_id' => $l1Level->id],
            ['name' => 'English', 'code' => 'ENG_L1', 'level_id' => $l1Level->id],

            ['name' => 'Advanced Mathematics', 'code' => 'MATH_M1', 'level_id' => $m1Level->id],
            ['name' => 'Advanced Physics', 'code' => 'PHYS_M1', 'level_id' => $m1Level->id],
            ['name' => 'Data Structures', 'code' => 'DS_M1', 'level_id' => $m1Level->id],
            ['name' => 'Technical English', 'code' => 'ENG_M1', 'level_id' => $m1Level->id],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        $this->command->info('8 Subjects created (4 per level: L1, M1)');
    }
}
