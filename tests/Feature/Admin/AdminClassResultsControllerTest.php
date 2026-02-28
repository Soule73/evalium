<?php

namespace Tests\Feature\Admin;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AdminClassResultsControllerTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->class = $this->createClassWithStudents(3);

        $academicYear = \App\Models\AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30']
        );
        $semester = \App\Models\Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'order_number' => 1],
            ['name' => 'Semester 1', 'start_date' => '2023-09-01', 'end_date' => '2024-01-31']
        );
        $subject = \App\Models\Subject::factory()->create(['level_id' => $this->class->level_id]);
        $classSubject = \App\Models\ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
            'coefficient' => 1,
            'valid_from' => now(),
        ]);

        $this->createAssessmentWithQuestions($this->teacher, [
            'class_subject_id' => $classSubject->id,
        ]);
    }

    #[Test]
    public function admin_can_access_class_results_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.results', $this->class));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Classes/Results')
                ->has('class')
                ->has('results')
                ->has('results.overview')
                ->has('results.assessment_stats')
                ->has('results.student_stats'),
        );
    }

    #[Test]
    public function admin_results_overview_contains_expected_keys(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.results', $this->class));

        $response->assertInertia(
            fn ($page) => $page
                ->has('results.overview.total_students')
                ->has('results.overview.total_assessments')
                ->has('results.overview.completion_rate'),
        );
    }

    #[Test]
    public function admin_sees_all_assessments_not_filtered_by_teacher(): void
    {
        $secondTeacher = $this->createTeacher(['email' => 'second.teacher@example.com']);

        $subject = \App\Models\Subject::factory()->create([
            'level_id' => $this->class->level_id,
        ]);
        $semester = \App\Models\Semester::first();
        $classSubject = \App\Models\ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $secondTeacher->id,
            'semester_id' => $semester->id,
            'coefficient' => 1,
            'valid_from' => now(),
        ]);

        $this->createAssessmentWithQuestions($secondTeacher, [
            'class_subject_id' => $classSubject->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.results', $this->class));

        $response->assertInertia(
            fn ($page) => $page
                ->where('results.overview.total_assessments', 2)
                ->has('results.assessment_stats', 2),
        );
    }

    #[Test]
    public function admin_results_shows_correct_student_count(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.results', $this->class));

        $response->assertInertia(
            fn ($page) => $page
                ->where('results.overview.total_students', $this->class->students->count()),
        );
    }

    #[Test]
    public function unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('admin.classes.results', $this->class));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function student_cannot_access_admin_class_results(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student)
            ->get(route('admin.classes.results', $this->class));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_cannot_access_admin_class_results(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('admin.classes.results', $this->class));

        $response->assertForbidden();
    }
}
