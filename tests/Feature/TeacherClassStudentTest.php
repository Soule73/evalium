<?php

namespace Tests\Feature;

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
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherClassStudentTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private Enrollment $enrollment;

    private ClassSubject $classSubject;

    private Assessment $assessment;

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

    public function test_teacher_can_view_class_student_show(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.students.show', [
                'class' => $this->class->id,
                'enrollment' => $this->enrollment->id,
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Classes/Students/Show')
                    ->has('class')
                    ->has('enrollment')
                    ->has('subjects')
                    ->has('overallStats')
                    ->where('routeContext.role', 'teacher')
            );
    }

    public function test_teacher_can_view_class_student_assignments(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.students.assignments', [
                'class' => $this->class->id,
                'enrollment' => $this->enrollment->id,
            ]))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Classes/Students/Assignments/Index')
                    ->has('class')
                    ->has('enrollment')
                    ->has('assignments')
                    ->where('routeContext.role', 'teacher')
            );
    }

    public function test_returns_404_when_enrollment_not_in_class(): void
    {
        $otherClass = ClassModel::factory()->create([
            'academic_year_id' => $this->class->academic_year_id,
            'level_id' => $this->class->level_id,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.classes.students.show', [
                'class' => $otherClass->id,
                'enrollment' => $this->enrollment->id,
            ]))
            ->assertStatus(404);
    }

    public function test_student_cannot_access_teacher_class_student_routes(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.classes.students.show', [
                'class' => $this->class->id,
                'enrollment' => $this->enrollment->id,
            ]))
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_class_student_routes(): void
    {
        $this->get(route('teacher.classes.students.show', [
            'class' => $this->class->id,
            'enrollment' => $this->enrollment->id,
        ]))
            ->assertRedirect(route('login'));
    }
}
