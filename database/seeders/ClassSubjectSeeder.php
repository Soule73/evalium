<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassSubjectSeeder extends Seeder
{
    /**
     * Assign teachers to subjects in classes with coefficients.
     * This is the CENTRAL seeder linking classes, subjects, teachers, and semesters.
     */
    public function run(): void
    {
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->get();

        if ($teachers->isEmpty()) {
            $this->command->error('✗ No teachers found. Ensure users with teacher role exist.');
            return;
        }

        $classes = ClassModel::with('level')->get();
        $semester1 = Semester::where('order_number', 1)->first();

        if (!$semester1) {
            $this->command->error('✗ No semester found. Run SemesterSeeder first.');
            return;
        }

        $coefficients = [
            'Mathématiques' => 7.0,
            'Physique' => 6.0,
            'Chimie' => 5.0,
            'Informatique' => 6.0,
            'Français' => 5.0,
            'Anglais' => 4.0,
            'Histoire' => 3.0,
            'Géographie' => 3.0,
        ];

        $count = 0;
        foreach ($classes as $class) {
            $subjects = Subject::where('level_id', $class->level_id)->get();

            foreach ($subjects as $subject) {
                $teacher = $teachers->random();
                $coefficient = $coefficients[$subject->name] ?? 4.0;

                ClassSubject::firstOrCreate(
                    [
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'semester_id' => $semester1->id,
                        'valid_to' => null,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'coefficient' => $coefficient,
                        'valid_from' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->command->info("✓ {$count} ClassSubject assignments created (teachers → subjects → classes)");
    }
}
