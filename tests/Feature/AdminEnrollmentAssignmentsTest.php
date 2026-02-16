<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AdminEnrollmentAssignmentsTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $admin;

    private User $student;

    private User $teacher;

    private ClassModel $class;

    private Enrollment $enrollment;

    private ClassSubject $classSubject;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();
        $subject = Subject::factory()->create(['level_id' => $level->id]);

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ]);

        $this->enrollment = $this->class->enrollments()->create([
            'student_id' => $this->student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'order_number' => 1],
            ['name' => 'S1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
        );

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
        ]);

        $this->assessment = Assessment::factory()->examen()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_admin_can_view_enrollment_assignments(): void
    {
        AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments', $this->enrollment))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Assignments/Index')
                    ->has('enrollment')
                    ->has('assignments.data', 1)
                    ->has('subjects')
            );
    }

    public function test_admin_can_filter_assignments_by_subject(): void
    {
        AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments', [
                'enrollment' => $this->enrollment->id,
                'class_subject_id' => $this->classSubject->id,
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Assignments/Index')
                    ->has('assignments.data', 1)
            );
    }

    public function test_admin_can_filter_assignments_by_status(): void
    {
        AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments', [
                'enrollment' => $this->enrollment->id,
                'status' => 'graded',
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->has('assignments.data', 1)
            );

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments', [
                'enrollment' => $this->enrollment->id,
                'status' => 'not_submitted',
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->has('assignments.data', 0)
            );
    }

    public function test_admin_can_view_assignment_detail(): void
    {
        $assignment = AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments.show', [
                'enrollment' => $this->enrollment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Assignments/Show')
                    ->has('enrollment')
                    ->has('assignment')
                    ->has('assessment')
                    ->has('userAnswers')
            );
    }

    public function test_unauthenticated_user_cannot_access_assignments(): void
    {
        $this->get(route('admin.enrollments.assignments', $this->enrollment))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_assignments(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.enrollments.assignments', $this->enrollment))
            ->assertStatus(403);
    }

    public function test_empty_assignments_returns_empty_list(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.assignments', $this->enrollment))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->has('assignments.data', 0)
            );
    }
}
