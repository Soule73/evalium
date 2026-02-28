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
     * Assign teachers to subjects in all 6 classes with rotating coefficients.
     */
    public function run(): void
    {
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->orderBy('id')->get();

        if ($teachers->count() < 6) {
            $this->command->error('Need at least 6 teachers.');

            return;
        }

        $semester1 = Semester::where('order_number', 1)->first();

        if (! $semester1) {
            $this->command->error('No semester found.');

            return;
        }

        $classes = ClassModel::with('level')->orderBy('level_id')->orderBy('name')->get();

        $coefficients = [
            'L1' => [4.0, 3.0, 4.0, 2.0],
            'L2' => [4.0, 3.0, 5.0, 2.0],
            'M1' => [5.0, 5.0, 4.0, 3.0],
        ];

        $count = 0;
        $teacherIndex = 0;

        foreach ($classes as $class) {
            $subjects = Subject::where('level_id', $class->level_id)->orderBy('id')->get();
            $levelCoeffs = $coefficients[$class->level->name] ?? [3.0, 3.0, 3.0, 3.0];

            foreach ($subjects as $subIndex => $subject) {
                ClassSubject::create([
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teachers[$teacherIndex % $teachers->count()]->id,
                    'semester_id' => $semester1->id,
                    'coefficient' => $levelCoeffs[$subIndex] ?? 3.0,
                    'valid_from' => now()->subMonths(3),
                ]);
                $teacherIndex++;
                $count++;
            }
        }

        $this->command->info("{$count} ClassSubject assignments created across {$classes->count()} classes");
    }
}
