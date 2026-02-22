<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherClassSubjectControllerTest extends TestCase
{
  use InteractsWithTestData, RefreshDatabase;

  private User $teacher;

  private AcademicYear $academicYear;

  private ClassSubject $classSubject;

  protected function setUp(): void
  {
    parent::setUp();
    $this->seedRolesAndPermissions();

    config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

    $this->teacher = $this->createTeacher();

    $this->academicYear = AcademicYear::firstOrCreate(
      ['is_current' => true],
      ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30'],
    );

    $level = Level::factory()->create();
    $subject = Subject::factory()->create(['level_id' => $level->id]);
    $class = ClassModel::factory()->create([
      'academic_year_id' => $this->academicYear->id,
      'level_id' => $level->id,
    ]);
    $semester = Semester::firstOrCreate(
      ['academic_year_id' => $this->academicYear->id, 'order_number' => 1],
      ['name' => 'S1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31'],
    );

    $this->classSubject = ClassSubject::factory()->create([
      'class_id' => $class->id,
      'subject_id' => $subject->id,
      'teacher_id' => $this->teacher->id,
      'semester_id' => $semester->id,
    ]);
  }

  #[Test]
  public function teacher_can_access_class_subjects_page(): void
  {
    $response = $this->actingAs($this->teacher)
      ->get(route('teacher.class-subjects.index'));

    $response->assertOk();
    $response->assertInertia(
      fn($page) => $page
        ->component('Teacher/ClassSubjects/Index')
        ->has('classSubjects')
        ->has('filters'),
    );
  }

  #[Test]
  public function teacher_sees_only_own_assignments(): void
  {
    $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

    $level = Level::factory()->create();
    $subject = Subject::factory()->create(['level_id' => $level->id]);
    $class = ClassModel::factory()->create([
      'academic_year_id' => $this->academicYear->id,
      'level_id' => $level->id,
    ]);
    $semester = Semester::firstOrCreate(
      ['academic_year_id' => $this->academicYear->id, 'order_number' => 2],
      ['name' => 'S2', 'start_date' => '2026-02-01', 'end_date' => '2026-06-30'],
    );

    ClassSubject::factory()->create([
      'class_id' => $class->id,
      'subject_id' => $subject->id,
      'teacher_id' => $otherTeacher->id,
      'semester_id' => $semester->id,
    ]);

    $response = $this->actingAs($this->teacher)
      ->get(route('teacher.class-subjects.index'));

    $response->assertOk();
    $response->assertInertia(
      fn($page) => $page
        ->component('Teacher/ClassSubjects/Index')
        ->where('classSubjects.total', 1),
    );
  }

  #[Test]
  public function guest_cannot_access_class_subjects_page(): void
  {
    $this->get(route('teacher.class-subjects.index'))
      ->assertRedirect(route('login'));
  }

  #[Test]
  public function student_cannot_access_class_subjects_page(): void
  {
    $student = $this->createStudent();

    $this->actingAs($student)
      ->get(route('teacher.class-subjects.index'))
      ->assertForbidden();
  }
}
