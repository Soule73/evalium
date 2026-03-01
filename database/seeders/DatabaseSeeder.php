<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic demo data.
     *
     * Order respects foreign key dependencies:
     * Roles > Levels > Users > AcademicYear > Semesters > Subjects >
     * Classes > Enrollments > ClassSubjects > Assessments > Assignments+Answers
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            LevelSeeder::class,
            UserSeeder::class,

            AcademicYearSeeder::class,
            SemesterSeeder::class,
            SubjectSeeder::class,
            ClassSeeder::class,
            EnrollmentSeeder::class,
            ClassSubjectSeeder::class,
            AssessmentSeeder::class,
            DemoAssignmentSeeder::class,
        ]);
    }
}
