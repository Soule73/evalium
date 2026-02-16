<?php

namespace Tests\Unit\Traits;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicYearScopeTraitsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear1;

    private AcademicYear $academicYear2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);

        // Create two academic years for testing
        $this->academicYear1 = AcademicYear::factory()->create(['name' => 'Year 2024-2025']);
        $this->academicYear2 = AcademicYear::factory()->create(['name' => 'Year 2025-2026']);
    }

    public function test_has_academic_year_scope_trait_filters_class_model(): void
    {
        // Create classes for both academic years
        $level = Level::factory()->create();
        $class1 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear1->id,
            'level_id' => $level->id,
        ]);
        $class2 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear2->id,
            'level_id' => $level->id,
        ]);

        // Test scope filtering
        $classes = ClassModel::forAcademicYear($this->academicYear1->id)->get();

        $this->assertCount(1, $classes);
        $this->assertEquals($class1->id, $classes->first()->id);
    }

    public function test_has_academic_year_scope_trait_filters_semester(): void
    {
        // Create semesters for both academic years
        $semester1 = Semester::factory()->create([
            'academic_year_id' => $this->academicYear1->id,
            'name' => 'S1 2024-2025',
        ]);

        // Test scope filtering
        $semesters = Semester::forAcademicYear($this->academicYear1->id)->get();

        $this->assertCount(1, $semesters);
        $this->assertEquals($semester1->id, $semesters->first()->id);
    }

    public function test_has_academic_year_through_class_trait_filters_enrollment(): void
    {
        // Create classes and enrollments
        $level = Level::factory()->create();
        $class1 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear1->id,
            'level_id' => $level->id,
        ]);

        $student = User::factory()->student()->create();
        $enrollment1 = Enrollment::factory()->create([
            'class_id' => $class1->id,
            'student_id' => $student->id,
        ]);

        // Test scope filtering
        $enrollments = Enrollment::forAcademicYear($this->academicYear1->id)->get();

        $this->assertCount(1, $enrollments);
        $this->assertEquals($enrollment1->id, $enrollments->first()->id);
    }

    public function test_has_academic_year_through_class_trait_filters_class_subject(): void
    {
        // Create classes and class subjects
        $level = Level::factory()->create();
        $subject = Subject::factory()->create(['level_id' => $level->id]);
        $teacher = User::factory()->teacher()->create();
        $semester1 = Semester::factory()->create(['academic_year_id' => $this->academicYear1->id]);

        $class1 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear1->id,
            'level_id' => $level->id,
        ]);

        $classSubject1 = ClassSubject::factory()->create([
            'class_id' => $class1->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester1->id,
        ]);

        // Test scope filtering
        $classSubjects = ClassSubject::forAcademicYear($this->academicYear1->id)->get();

        $this->assertCount(1, $classSubjects);
        $this->assertEquals($classSubject1->id, $classSubjects->first()->id);
    }

    public function test_assessment_filters_through_class_subject_class_relationship(): void
    {
        // Create complex assessment structure
        $level = Level::factory()->create();
        $subject = Subject::factory()->create(['level_id' => $level->id]);
        $teacher = User::factory()->teacher()->create();
        $semester1 = Semester::factory()->create(['academic_year_id' => $this->academicYear1->id]);
        $semester2 = Semester::factory()->create(['academic_year_id' => $this->academicYear2->id]);

        $class1 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear1->id,
            'level_id' => $level->id,
        ]);
        $class2 = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear2->id,
            'level_id' => $level->id,
        ]);

        $classSubject1 = ClassSubject::factory()->create([
            'class_id' => $class1->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester1->id,
        ]);
        $classSubject2 = ClassSubject::factory()->create([
            'class_id' => $class2->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester2->id,
        ]);

        $assessment1 = Assessment::factory()->create([
            'class_subject_id' => $classSubject1->id,
            'teacher_id' => $teacher->id,
        ]);

        // Test scope filtering through nested relationship
        $assessments = Assessment::forAcademicYear($this->academicYear1->id)->get();

        $this->assertCount(1, $assessments);
        $this->assertEquals($assessment1->id, $assessments->first()->id);
    }
}
