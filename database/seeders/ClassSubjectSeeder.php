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

        if ($teachers->count() < 4) {
            $this->command->error('Need at least 4 teachers. Run UserSeeder first.');

            return;
        }

        $l1Class = ClassModel::whereHas('level', function ($query) {
            $query->where('name', 'L1');
        })->first();

        $m1Class = ClassModel::whereHas('level', function ($query) {
            $query->where('name', 'M1');
        })->first();

        $semester1 = Semester::where('order_number', 1)->first();

        if (! $semester1) {
            $this->command->error('No semester found. Run SemesterSeeder first.');

            return;
        }

        if (! $l1Class || ! $m1Class) {
            $this->command->error('L1 or M1 class not found.');

            return;
        }

        $l1Subjects = Subject::where('level_id', $l1Class->level_id)->get();
        $m1Subjects = Subject::where('level_id', $m1Class->level_id)->get();

        $teachersArray = $teachers->toArray();

        $count = 0;
        foreach ($l1Subjects as $index => $subject) {
            ClassSubject::create([
                'class_id' => $l1Class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teachersArray[$index % count($teachersArray)]['id'],
                'semester_id' => $semester1->id,
                'coefficient' => 4.0,
                'valid_from' => now(),
            ]);
            $count++;
        }

        foreach ($m1Subjects as $index => $subject) {
            ClassSubject::create([
                'class_id' => $m1Class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teachersArray[$index % count($teachersArray)]['id'],
                'semester_id' => $semester1->id,
                'coefficient' => 5.0,
                'valid_from' => now(),
            ]);
            $count++;
        }

        $this->command->info("{$count} ClassSubject assignments created (4 per class with rotating teachers)");
    }
}
