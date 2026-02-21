<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherSubjectTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private User $student;

    private Subject $subject;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();
        $this->subject = Subject::factory()->create(['level_id' => $level->id]);

        $class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ]);

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'order_number' => 1],
            ['name' => 'S1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
        );

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
        ]);
    }

    public function test_teacher_can_view_subjects_index(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.subjects.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Subjects/Index')
                    ->has('subjects')
                    ->has('classes')
                    ->where('routeContext.role', 'teacher')
                    ->where('routeContext.editRoute', null)
            );
    }

    public function test_teacher_can_view_subject_show(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.subjects.show', $this->subject))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Subjects/Show')
                    ->has('subject')
                    ->has('assessments')
                    ->where('routeContext.role', 'teacher')
                    ->where('routeContext.assessmentShowRoute', 'teacher.assessments.show')
            );
    }

    public function test_student_cannot_access_teacher_subjects(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.subjects.index'))
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_teacher_subjects(): void
    {
        $this->get(route('teacher.subjects.index'))
            ->assertRedirect(route('login'));
    }
}
