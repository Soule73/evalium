<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentEnrollmentControllerTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $student;

    private ClassModel $class;

    private AcademicYear $academicYear;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level->id,
        ]);

        $this->student = $this->createStudent();

        $this->class->enrollments()->create([
            'student_id' => $this->student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $level->id]);

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $this->academicYear->id, 'order_number' => 1],
            ['name' => 'Semester 1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
        );

        $this->classSubject = ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
            'coefficient' => 2,
            'valid_from' => now(),
        ]);
    }

    public function test_show_returns_enrollment_page(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->has('enrollment')
                ->has('subjects')
                ->has('overallStats')
                ->has('filters')
        );
    }

    public function test_show_redirects_when_not_enrolled(): void
    {
        $unenrolledStudent = $this->createStudent();

        $response = $this->actingAs($unenrolledStudent)
            ->get(route('student.enrollment.show'));

        $response->assertRedirect();
    }

    public function test_show_includes_subject_stats_with_grades(): void
    {
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'coefficient' => 1,
            'settings' => ['is_published' => true],
        ]);

        $question = \App\Models\Question::factory()->create([
            'assessment_id' => $assessment->id,
            'points' => 20,
        ]);

        $this->createGradedAssignment($assessment, $this->student, 15);

        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->has('subjects.data', 1)
                ->where('subjects.data.0.subject_name', $this->classSubject->subject->name)
                ->where('subjects.data.0.coefficient', $this->classSubject->coefficient)
                ->where('subjects.data.0.completed_count', 1)
        );
    }

    public function test_show_computes_overall_stats_from_loaded_data(): void
    {
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'coefficient' => 1,
            'settings' => ['is_published' => true],
        ]);

        \App\Models\Question::factory()->create([
            'assessment_id' => $assessment->id,
            'points' => 20,
        ]);

        $this->createGradedAssignment($assessment, $this->student, 16);

        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->has('overallStats.subjects', 1)
                ->has('overallStats.annual_average')
                ->where('overallStats.student_id', $this->student->id)
                ->where('overallStats.class_id', $this->class->id)
        );
    }

    public function test_show_supports_search_filter(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show', ['search' => 'nonexistent']));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->where('filters.search', 'nonexistent')
        );
    }

    public function test_show_paginates_subjects(): void
    {
        $teacher = $this->classSubject->teacher;
        $level = $this->class->level;
        $semester = Semester::where('academic_year_id', $this->academicYear->id)->first();

        for ($i = 0; $i < 12; $i++) {
            $subject = Subject::create([
                'name' => "Subject Paginate {$i}",
                'code' => "PAG{$i}",
                'level_id' => $level->id,
            ]);
            ClassSubject::create([
                'class_id' => $this->class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'semester_id' => $semester->id,
                'coefficient' => 1,
                'valid_from' => now(),
            ]);
        }

        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show', ['per_page' => 5, 'page' => 1]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->has('subjects.data', 5)
                ->where('subjects.total', 13)
        );
    }

    public function test_show_overall_stats_include_all_subjects_even_when_paginated(): void
    {
        $teacher = $this->classSubject->teacher;
        $level = $this->class->level;
        $semester = Semester::where('academic_year_id', $this->academicYear->id)->first();

        for ($i = 0; $i < 4; $i++) {
            $subject = Subject::create([
                'name' => "Subject Overall {$i}",
                'code' => "OVR{$i}",
                'level_id' => $level->id,
            ]);
            ClassSubject::create([
                'class_id' => $this->class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'semester_id' => $semester->id,
                'coefficient' => 1,
                'valid_from' => now(),
            ]);
        }

        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.show', ['per_page' => 2, 'page' => 1]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Show')
                ->has('subjects.data', 2)
                ->has('overallStats.subjects', 5)
        );
    }

    public function test_classmates_returns_classmates_page(): void
    {
        $classmate = $this->createStudent();
        $this->class->enrollments()->create([
            'student_id' => $classmate->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.classmates'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/Classmates')
                ->has('enrollment')
                ->has('classmates', 1)
        );
    }

    public function test_history_returns_history_page(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.enrollment.history'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Enrollment/History')
                ->has('enrollments')
        );
    }
}
