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

        if ($students->count() < 20) {
            $this->command->error('Need at least 20 students. Run UserSeeder first.');

            return;
        }

        $l1Class = ClassModel::whereHas('level', function ($query) {
            $query->where('name', 'L1');
        })->first();

        $m1Class = ClassModel::whereHas('level', function ($query) {
            $query->where('name', 'M1');
        })->first();

        if (! $l1Class || ! $m1Class) {
            $this->command->error('L1 or M1 class not found. Run ClassSeeder first.');

            return;
        }

        $count = 0;
        foreach ($students->take(10) as $student) {
            Enrollment::create([
                'class_id' => $l1Class->id,
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
            $count++;
        }

        foreach ($students->skip(10)->take(10) as $student) {
            Enrollment::create([
                'class_id' => $m1Class->id,
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
            $count++;
        }

        $this->command->info("{$count} Students enrolled (10 in L1-A, 10 in M1-A)");
    }
}
