<?php

namespace Tests\Feature\Teacher;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

/**
 * Tests the grading access guard that prevents grading unsubmitted assignments
 * when the assessment is still in progress.
 */
class TeacherGradingAccessGuardTest extends TestCase
{
  use InteractsWithTestData, RefreshDatabase;

  private User $teacher;

  private Assessment $supervisedAssessment;

  private Assessment $homeworkAssessment;

  private User $student;

  protected function setUp(): void
  {
    parent::setUp();
    $this->seedRolesAndPermissions();
    config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

    $this->teacher = $this->createTeacher();
    $this->student = $this->createStudent();

    $this->supervisedAssessment = $this->createAssessmentWithQuestions($this->teacher, [
      'delivery_mode' => 'supervised',
      'scheduled_at' => null,
      'duration_minutes' => null,
    ]);

    $this->homeworkAssessment = $this->createAssessmentWithQuestions($this->teacher, [
      'delivery_mode' => 'homework',
      'due_date' => null,
    ]);

    $this->ensureStudentEnrolled($this->student, $this->supervisedAssessment);
    $this->ensureStudentEnrolled($this->student, $this->homeworkAssessment);
  }

  #[Test]
  public function teacher_can_grade_submitted_assignment(): void
  {
    $assignment = $this->createSubmittedAssignment($this->supervisedAssessment, $this->student);

    $this->actingAs($this->teacher)
      ->get(route('teacher.assessments.grade', [
        'assessment' => $this->supervisedAssessment->id,
        'assignment' => $assignment->id,
      ]))
      ->assertOk()
      ->assertInertia(fn($page) => $page->component('Assessments/Grade'));
  }

  #[Test]
  public function teacher_cannot_grade_in_progress_assignment_when_supervised_still_running(): void
  {
    $this->supervisedAssessment->update([
      'scheduled_at' => now()->subMinutes(10),
      'duration_minutes' => 120,
    ]);

    $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
      'started_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($this->teacher)
      ->get(route('teacher.assessments.grade', [
        'assessment' => $this->supervisedAssessment->id,
        'assignment' => $assignment->id,
      ]))
      ->assertRedirect();
  }

  #[Test]
  public function teacher_can_grade_not_submitted_assignment_when_supervised_assessment_ended(): void
  {
    $this->supervisedAssessment->update([
      'scheduled_at' => now()->subHours(3),
      'duration_minutes' => 60,
    ]);

    $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
      'started_at' => now()->subHours(3),
    ]);

    $this->actingAs($this->teacher)
      ->get(route('teacher.assessments.grade', [
        'assessment' => $this->supervisedAssessment->id,
        'assignment' => $assignment->id,
      ]))
      ->assertOk()
      ->assertInertia(
        fn($page) => $page
          ->component('Assessments/Grade')
          ->where('gradingState.reason', 'not_submitted_assessment_ended')
          ->where('gradingState.warning', 'grading_without_submission')
      );
  }

  #[Test]
  public function teacher_can_grade_not_submitted_homework_after_due_date(): void
  {
    $this->homeworkAssessment->update(['due_date' => now()->subHours(2)]);

    $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

    $this->actingAs($this->teacher)
      ->get(route('teacher.assessments.grade', [
        'assessment' => $this->homeworkAssessment->id,
        'assignment' => $assignment->id,
      ]))
      ->assertOk()
      ->assertInertia(
        fn($page) => $page
          ->component('Assessments/Grade')
          ->where('gradingState.allowed', true)
          ->where('gradingState.reason', 'not_submitted_assessment_ended')
      );
  }

  #[Test]
  public function save_grade_blocked_when_supervised_assessment_still_running(): void
  {
    $this->supervisedAssessment->update([
      'scheduled_at' => now()->subMinutes(10),
      'duration_minutes' => 120,
    ]);

    $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
      'started_at' => now()->subMinutes(5),
    ]);

    $scores = $this->buildScoresPayload($this->supervisedAssessment);

    $this->actingAs($this->teacher)
      ->post(route('teacher.assessments.saveGrade', [
        'assessment' => $this->supervisedAssessment->id,
        'assignment' => $assignment->id,
      ]), ['scores' => $scores, 'teacher_notes' => null])
      ->assertStatus(422);

    $this->assertNull($assignment->fresh()->graded_at);
  }

  #[Test]
  public function save_grade_allowed_after_supervised_assessment_ended_without_submission(): void
  {
    $this->supervisedAssessment->update([
      'scheduled_at' => now()->subHours(3),
      'duration_minutes' => 60,
    ]);

    $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
      'started_at' => now()->subHours(3),
    ]);

    $scores = $this->buildScoresPayload($this->supervisedAssessment);

    $this->actingAs($this->teacher)
      ->post(route('teacher.assessments.saveGrade', [
        'assessment' => $this->supervisedAssessment->id,
        'assignment' => $assignment->id,
      ]), ['scores' => $scores, 'teacher_notes' => 'Absent'])
      ->assertRedirect();

    $this->assertNotNull($assignment->fresh()->graded_at);
  }

  /**
   * Build a valid scores payload for the given assessment's questions.
   *
   * @return array<int, array{question_id: int, score: int, feedback: null}>
   */
  private function buildScoresPayload(Assessment $assessment): array
  {
    $assessment->loadMissing('questions');

    return $assessment->questions->map(fn($q) => [
      'question_id' => $q->id,
      'score' => 0,
      'feedback' => null,
    ])->all();
  }

  /**
   * Ensure the student is enrolled in the class linked to the given assessment.
   */
  private function ensureStudentEnrolled(User $student, Assessment $assessment): void
  {
    $assessment->loadMissing('classSubject');

    \App\Models\Enrollment::firstOrCreate(
      ['student_id' => $student->id, 'class_id' => $assessment->classSubject->class_id],
      ['enrolled_at' => now(), 'status' => 'active']
    );
  }
}
