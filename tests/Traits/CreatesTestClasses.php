<?php

namespace Tests\Traits;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\User;

trait CreatesTestClasses
{
    /**
     * Create a class with enrolled students.
     */
    protected function createClassWithStudents(
        int $studentCount = 3,
        array $classAttributes = []
    ): ClassModel {
        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30']
        );
        $level = Level::factory()->create();

        $class = ClassModel::factory()->create(array_merge([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ], $classAttributes));

        if ($studentCount > 0) {
            $students = User::factory()
                ->count($studentCount)
                ->create()
                ->each(fn ($student) => $student->assignRole('student'));

            foreach ($students as $student) {
                $class->enrollments()->create([
                    'student_id' => $student->id,
                    'enrolled_at' => now(),
                ]);
            }

            $class->load('students');
        }

        return $class;
    }

    /**
     * Assign an assessment to a class.
     * In the new architecture, assessments are linked to classes via class_subject_id.
     * This method updates the assessment's class_subject to match the given class.
     */
    protected function assignAssessmentToClass(
        Assessment $assessment,
        ClassModel $class,
        User $teacher
    ): void {
        $classSubject = $class->classSubjects()
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $classSubject) {
            $classSubject = $class->classSubjects()->first();
        }

        if ($classSubject) {
            $assessment->update(['class_subject_id' => $classSubject->id]);
        }
    }

    /**
     * Create an empty class without students.
     */
    protected function createEmptyClass(array $classAttributes = []): ClassModel
    {
        return $this->createClassWithStudents(0, $classAttributes);
    }

    /**
     * Add students to an existing class.
     */
    protected function addStudentsToClass(ClassModel $class, array $students): void
    {
        foreach ($students as $student) {
            $class->enrollments()->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
            ]);
        }

        $class->load('students');
    }

    /**
     * Create a level for testing.
     */
    protected function createLevel(array $attributes = []): Level
    {
        return Level::factory()->create($attributes);
    }
}
