<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * NEW MCD Architecture Order:
     * 1. Roles & Permissions
     * 2. Levels (already exists)
     * 3. Users (admin, teachers, students)
     * 4. Academic Year 2025/2026
     * 5. Semesters (2 per year)
     * 6. Subjects (per level)
     * 7. Classes (per academic year)
     * 8. Enrollments (students → classes)
     * 9. ClassSubjects ⭐ CENTRAL (teacher → subject → class)
     * 10. Assessments (evaluations per class subject)
     *
     * LEGACY seeders (Groups, Exams) preserved for backward compatibility
     */
    public function run(): void
    {
        $this->call([
            // === PHASE 1: Base Configuration ===
            RoleAndPermissionSeeder::class,
            LevelSeeder::class,
            UserSeeder::class,

            // === PHASE 2: NEW MCD Architecture ===
            AcademicYearSeeder::class,
            SemesterSeeder::class,
            SubjectSeeder::class,
            ClassSeeder::class,
            EnrollmentSeeder::class,
            ClassSubjectSeeder::class,
            AssessmentSeeder::class,

            // === PHASE 3: LEGACY (temporarily disabled - need adaptation) ===
            // GroupSeeder::class,
            // ExamSeeder::class,
            // ExamGroupAssignmentSeeder::class,
        ]);
    }
}
