<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentAssessmentTakeTest extends TestCase
{
  use InteractsWithTestData, RefreshDatabase;

  private User $student;

  private User $teacher;

  private ClassModel $class;

  private AcademicYear $academicYear;

  private ClassSubject $classSubject;

  private Assessment $assessment;

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

    $this->teacher = $this->createTeacher();
    $subject = Subject::factory()->create(['level_id' => $level->id]);

    $semester = Semester::firstOrCreate(
      ['academic_year_id' => $this->academicYear->id, 'order_number' => 1],
      ['name' => 'Semester 1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
    );

    $this->classSubject = ClassSubject::create([
      'class_id' => $this->class->id,
      'subject_id' => $subject->id,
      'teacher_id' => $this->teacher->id,
      'semester_id' => $semester->id,
      'coefficient' => 2,
      'valid_from' => now(),
    ]);

    $this->assessment = Assessment::factory()->create([
      'class_subject_id' => $this->classSubject->id,
      'teacher_id' => $this->teacher->id,
      'coefficient' => 1,
      'duration_minutes' => 60,
      'settings' => ['is_published' => true],
    ]);
  }

  public function test_enrolled_student_can_take_assessment(): void
  {
    Question::factory()->count(2)->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'one_choice',
      'points' => 10,
    ]);

    $response = $this->actingAs($this->student)
      ->get(route('student.assessments.take', $this->assessment));

    $response->assertOk();
    $response->assertInertia(
      fn($page) => $page
        ->component('Student/Assessments/Take')
        ->has('assessment')
        ->has('assignment')
        ->has('questions')
        ->has('userAnswers')
    );
  }

  public function test_unenrolled_student_cannot_take_assessment(): void
  {
    $otherStudent = $this->createStudent();

    $response = $this->actingAs($otherStudent)
      ->get(route('student.assessments.take', $this->assessment));

    $response->assertForbidden();
  }

  public function test_submitted_assessment_redirects_to_show(): void
  {
    AssessmentAssignment::create([
      'assessment_id' => $this->assessment->id,
      'student_id' => $this->student->id,
      'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
      ->get(route('student.assessments.take', $this->assessment));

    $response->assertRedirect(route('student.assessments.show', $this->assessment));
  }

  public function test_save_answers_requires_enrollment(): void
  {
    $otherStudent = $this->createStudent();

    $response = $this->actingAs($otherStudent)
      ->postJson(route('student.assessments.save-answers', $this->assessment), [
        'answers' => [1 => 'test'],
      ]);

    $response->assertForbidden();
  }

  public function test_save_answers_stores_answers(): void
  {
    $question = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'text',
      'points' => 10,
    ]);

    $response = $this->actingAs($this->student)
      ->postJson(route('student.assessments.save-answers', $this->assessment), [
        'answers' => [$question->id => 'My answer text'],
      ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Answers saved successfully']);

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertNotNull($assignment);
    $this->assertEquals('My answer text', $assignment->answers()->first()->answer_text);
  }

  public function test_save_answers_stores_multiple_choice(): void
  {
    $question = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'multiple',
      'points' => 10,
    ]);

    $choiceA = $question->choices()->create(['content' => 'A', 'is_correct' => true, 'order_index' => 0]);
    $choiceB = $question->choices()->create(['content' => 'B', 'is_correct' => true, 'order_index' => 1]);

    $response = $this->actingAs($this->student)
      ->postJson(route('student.assessments.save-answers', $this->assessment), [
        'answers' => [$question->id => [$choiceA->id, $choiceB->id]],
      ]);

    $response->assertOk();

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertCount(2, $assignment->answers);
  }

  public function test_save_answers_rejected_after_submission(): void
  {
    AssessmentAssignment::create([
      'assessment_id' => $this->assessment->id,
      'student_id' => $this->student->id,
      'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
      ->postJson(route('student.assessments.save-answers', $this->assessment), [
        'answers' => [1 => 'test'],
      ]);

    $response->assertStatus(400);
  }

  public function test_submit_requires_enrollment(): void
  {
    $otherStudent = $this->createStudent();

    $response = $this->actingAs($otherStudent)
      ->post(route('student.assessments.submit', $this->assessment), [
        'answers' => [1 => 'test'],
      ]);

    $response->assertForbidden();
  }

  public function test_submit_marks_assignment_as_submitted(): void
  {
    $question = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'text',
      'points' => 10,
    ]);

    $response = $this->actingAs($this->student)
      ->post(route('student.assessments.submit', $this->assessment), [
        'answers' => [$question->id => 'My answer'],
      ]);

    $response->assertRedirect(route('student.assessments.results', $this->assessment));

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertNotNull($assignment->submitted_at);
  }

  public function test_submit_auto_scores_non_text_questions(): void
  {
    $question = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'one_choice',
      'points' => 10,
    ]);

    $correct = $question->choices()->create(['content' => 'Right', 'is_correct' => true, 'order_index' => 0]);
    $question->choices()->create(['content' => 'Wrong', 'is_correct' => false, 'order_index' => 1]);

    $response = $this->actingAs($this->student)
      ->post(route('student.assessments.submit', $this->assessment), [
        'answers' => [$question->id => $correct->id],
      ]);

    $response->assertRedirect();

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertNotNull($assignment->graded_at);
    $this->assertEquals(10.00, (float) $assignment->score);
  }

  public function test_submit_does_not_auto_grade_with_text_questions(): void
  {
    Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'one_choice',
      'points' => 5,
    ]);

    $textQuestion = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'text',
      'points' => 5,
    ]);

    $response = $this->actingAs($this->student)
      ->post(route('student.assessments.submit', $this->assessment), [
        'answers' => [$textQuestion->id => 'My essay'],
      ]);

    $response->assertRedirect();

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertNull($assignment->graded_at);
  }

  public function test_submit_rejected_after_already_submitted(): void
  {
    AssessmentAssignment::create([
      'assessment_id' => $this->assessment->id,
      'student_id' => $this->student->id,
      'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
      ->post(route('student.assessments.submit', $this->assessment), [
        'answers' => [1 => 'test'],
      ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
  }

  public function test_security_violation_requires_enrollment(): void
  {
    $otherStudent = $this->createStudent();

    $response = $this->actingAs($otherStudent)
      ->postJson(route('student.assessments.security-violation', $this->assessment), [
        'violation_type' => 'tab_switch',
      ]);

    $response->assertForbidden();
  }

  public function test_security_violation_terminates_assessment(): void
  {
    $question = Question::factory()->create([
      'assessment_id' => $this->assessment->id,
      'type' => 'text',
      'points' => 10,
    ]);

    AssessmentAssignment::create([
      'assessment_id' => $this->assessment->id,
      'student_id' => $this->student->id,
    ]);

    $response = $this->actingAs($this->student)
      ->postJson(route('student.assessments.security-violation', $this->assessment), [
        'violation_type' => 'tab_switch',
        'violation_details' => 'Student switched tabs',
        'answers' => [$question->id => 'partial answer'],
      ]);

    $response->assertOk();

    $assignment = AssessmentAssignment::where('assessment_id', $this->assessment->id)
      ->where('student_id', $this->student->id)
      ->first();

    $this->assertNotNull($assignment->submitted_at);
    $this->assertTrue((bool) $assignment->forced_submission);
    $this->assertStringContains('tab_switch', $assignment->security_violation);
  }

  public function test_non_student_cannot_access_take_routes(): void
  {
    $response = $this->actingAs($this->teacher)
      ->get(route('student.assessments.take', $this->assessment));

    $response->assertForbidden();
  }

  private function assertStringContains(string $needle, string $haystack): void
  {
    $this->assertTrue(
      str_contains($haystack, $needle),
      "Failed asserting that '$haystack' contains '$needle'."
    );
  }
}
