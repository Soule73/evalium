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

class TeacherClassAssessmentTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private Assessment $assessment;

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

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
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

    public function test_teacher_can_view_class_assessments_index(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.assessments', $this->class))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Classes/Assessments')
                    ->has('class')
                    ->has('assessments')
                    ->has('filters')
                    ->has('subjects')
                    ->where('routeContext.role', 'teacher')
            );
    }

    public function test_teacher_class_assessments_returns_only_their_assessments(): void
    {
        $otherTeacher = $this->createTeacher();
        $otherSubject = Subject::factory()->create(['level_id' => $this->class->level_id]);
        $otherClassSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $otherTeacher->id,
            'semester_id' => $this->classSubject->semester_id,
        ]);
        Assessment::factory()->examen()->create([
            'class_subject_id' => $otherClassSubject->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.assessments', $this->class))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->has('assessments.data', 1)
            );
    }

    public function test_teacher_can_view_class_assessment_show(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.assessments.show', [
                'class' => $this->class->id,
                'assessment' => $this->assessment->id,
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Show')
                    ->has('assessment')
                    ->has('assignments')
                    ->where('routeContext.role', 'teacher')
                    ->where('routeContext.showRoute', null)
            );
    }

    public function test_student_cannot_access_class_assessments(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.classes.assessments', $this->class))
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_class_assessments(): void
    {
        $this->get(route('teacher.classes.assessments', $this->class))
            ->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_view_another_teachers_assessment(): void
    {
        $otherTeacher = $this->createTeacher();

        $this->actingAs($otherTeacher)
            ->get(route('teacher.classes.assessments.show', [
                'class' => $this->class->id,
                'assessment' => $this->assessment->id,
            ]))
            ->assertStatus(403);
    }
}
