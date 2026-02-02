<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Enroll existing students into classes.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->get();

        if ($students->isEmpty()) {
            $this->command->warn('⚠ No students found. Skipping enrollments.');
            return;
        }

        $classes = ClassModel::all();

        if ($classes->isEmpty()) {
            $this->command->error('✗ No classes found. Run ClassSeeder first.');
            return;
        }

        $count = 0;
        foreach ($students as $student) {
            $randomClass = $classes->random();

            Enrollment::firstOrCreate(
                [
                    'class_id' => $randomClass->id,
                    'student_id' => $student->id,
                ],
                [
                    'enrolled_at' => now(),
                    'status' => 'active',
                ]
            );
            $count++;
        }

        $this->command->info("✓ {$count} Students enrolled in classes");
    }
}
