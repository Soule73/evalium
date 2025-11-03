<?php

namespace Tests\Feature\Controllers\Student;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamAssignment;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamSecurityViolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser le seeder pour créer les rôles et permissions
        $this->seed(RoleAndPermissionSeeder::class);
    }

    /** @test */
    public function security_violation_changes_status_to_submitted()
    {
        // Arrange: Create teacher, student, exam with questions
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
            'duration' => 60,
        ]);

        // Create a question so we can calculate auto_score
        Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple', // Correction: utiliser 'multiple' au lieu de 'multiple_choice'
            'points' => 10,
        ]);

        // Create a started assignment
        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'started',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // Act: Report a security violation
        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Student switched tabs',
                'answers' => []
            ]
        );

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'exam_terminated' => true,
            'violation_type' => 'tab_switch',
        ]);

        // Assert: Check database - status should be submitted
        $this->assertDatabaseHas('exam_assignments', [
            'id' => $assignment->id,
            'status' => 'submitted',
            'security_violation' => 'tab_switch',
            'forced_submission' => true,
        ]);

        // Verify the assignment was updated
        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertEquals('tab_switch', $assignment->security_violation);
        $this->assertTrue($assignment->forced_submission);
        $this->assertNotNull($assignment->submitted_at);
    }

    /** @test */
    public function security_violation_saves_auto_score()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
            'duration' => 60,
        ]);

        // Create questions with auto-correctable answers
        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple', // Utiliser 'multiple' au lieu de 'multiple_choice'
            'points' => 10,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'started',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // Act: Report violation
        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'fullscreen_exit',
                'violation_details' => 'Student exited fullscreen',
                'answers' => []
            ]
        );

        // Assert
        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertNotNull($assignment->auto_score);
        $this->assertNotNull($assignment->submitted_at);
    }

    /** @test */
    public function non_terminal_violation_does_not_change_status()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'started',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // Act: Report a non-terminal violation (copy_paste)
        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'copy_paste',
                'violation_details' => 'Student attempted to copy',
                'answers' => []
            ]
        );

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'exam_terminated' => false,
        ]);

        // Status should still be started for non-terminal violations
        $assignment->refresh();
        $this->assertEquals('started', $assignment->status);
        $this->assertEquals('copy_paste', $assignment->security_violation);
        $this->assertFalse($assignment->forced_submission);
    }

    /** @test */
    public function cannot_report_violation_for_exam_not_started()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        // Create assignment but not started
        ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Act: Try to report violation
        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Test',
                'answers' => []
            ]
        );

        // Assert: Should fail because exam is not started
        $response->assertStatus(404);
    }

    /** @test */
    public function security_violation_saves_student_answers()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'started',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // Act: Report violation with answers
        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Student switched tabs',
                'answers' => [
                    $question->id => 'My answer before violation'
                ]
            ]
        );

        // Assert
        $response->assertStatus(200);

        // Check that answer was saved
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'My answer before violation',
        ]);
    }
}
