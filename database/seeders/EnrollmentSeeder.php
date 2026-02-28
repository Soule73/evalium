<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Distribute students across 6 classes with realistic enrollment.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->orderBy('id')->get();

        $classes = ClassModel::with('level')
            ->orderBy('level_id')
            ->orderBy('name')
            ->get();

        if ($students->isEmpty() || $classes->isEmpty()) {
            $this->command->error('Need students and classes. Run UserSeeder and ClassSeeder first.');

            return;
        }

        $distribution = [
            0 => 9,   // L1-A: 9
            1 => 8,   // L1-B: 8
            2 => 8,   // L2-A: 8
            3 => 7,   // L2-B: 7
            4 => 7,   // M1-A: 7
            5 => 6,   // M1-B: 6
        ];

        $studentIndex = 0;
        $count = 0;

        foreach ($classes as $classIndex => $class) {
            $qty = $distribution[$classIndex] ?? 7;
            $classStudents = $students->slice($studentIndex, $qty);

            foreach ($classStudents as $student) {
                Enrollment::create([
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'enrolled_at' => now()->subMonths(rand(2, 4)),
                    'status' => 'active',
                ]);
                $count++;
            }

            $studentIndex += $qty;
        }

        $this->command->info("{$count} Students enrolled across {$classes->count()} classes");
    }
}
