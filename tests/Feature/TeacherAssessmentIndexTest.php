<?php

namespace Tests\Feature;

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

class TeacherAssessmentIndexTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private User $student;

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
        $subject = Subject::factory()->create(['level_id' => $level->id]);

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
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
        ]);
    }

    public function test_teacher_can_view_assessments_index(): void
    {
        Assessment::factory()->count(2)->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Index')
                    ->has('assessments')
                    ->has('classes')
                    ->where('routeContext.role', 'teacher')
                    ->where('routeContext.createRoute', 'teacher.assessments.create')
                    ->where('routeContext.showRoute', 'teacher.assessments.show')
            );
    }

    public function test_teacher_only_sees_own_assessments(): void
    {
        $otherTeacher = $this->createTeacher();
        $otherSubject = Subject::factory()->create();
        $otherClassSubject = ClassSubject::factory()->create([
            'class_id' => $this->classSubject->class_id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $otherTeacher->id,
            'semester_id' => $this->classSubject->semester_id,
        ]);

        Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        Assessment::factory()->create([
            'class_subject_id' => $otherClassSubject->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->has('assessments.data', 1)
            );
    }

    public function test_student_cannot_access_teacher_assessments_index(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.assessments.index'))
            ->assertStatus(403);
    }

    public function test_unauthenticated_is_redirected_to_login(): void
    {
        $this->get(route('teacher.assessments.index'))
            ->assertRedirect(route('login'));
    }
}
