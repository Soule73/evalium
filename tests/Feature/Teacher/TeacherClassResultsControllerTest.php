<?php

namespace Tests\Feature\Teacher;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherClassResultsControllerTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->teacher = $this->createTeacher();
        $this->class = $this->createClassWithStudents(3);

        $assessment = $this->createAssessmentWithQuestions($this->teacher);
        $this->assignAssessmentToClass($assessment, $this->class, $this->teacher);
    }

    #[Test]
    public function teacher_can_access_class_results_page(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Teacher/Classes/Results')
                ->has('class')
                ->has('results')
                ->has('results.overview')
                ->has('results.assessment_stats')
                ->has('results.student_stats'),
        );
    }

    #[Test]
    public function results_overview_contains_expected_keys(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertInertia(
            fn ($page) => $page
                ->has('results.overview.total_students')
                ->has('results.overview.total_assessments')
                ->has('results.overview.completion_rate'),
        );
    }

    #[Test]
    public function results_shows_assessments_assigned_to_class(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertInertia(
            fn ($page) => $page
                ->where('results.overview.total_students', $this->class->students->count()),
        );
    }

    #[Test]
    public function unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('teacher.classes.results', $this->class));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function student_cannot_access_class_results_page(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_access_teacher_class_results_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertOk();
    }

    #[Test]
    public function teacher_without_assignments_sees_empty_assessment_stats(): void
    {
        $otherTeacher = $this->createTeacher(['email' => 'other.teacher@example.com']);

        $response = $this->actingAs($otherTeacher)
            ->get(route('teacher.classes.results', $this->class));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('results.assessment_stats', [])
                ->where('results.overview.total_assessments', 0),
        );
    }
}
