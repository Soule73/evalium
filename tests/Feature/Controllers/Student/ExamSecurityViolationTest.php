<?php

namespace Tests\Feature\Controllers\Student;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamAssignment;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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
            'type' => 'multiple', // Correction: utiliser 'multiple' au lieu de 'multiple'
            'points' => 10,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
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

    #[Test]
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
            'type' => 'multiple', // Utiliser 'multiple' au lieu de 'multiple'
            'points' => 10,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

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

    #[Test]
    public function security_violation_records_violation_type()
    {
        // Test différents types de violations
        $violationTypes = ['tab_switch', 'fullscreen_exit', 'browser_change', 'copy_paste'];

        foreach ($violationTypes as $type) {
            // Arrange - créer un nouvel exam pour chaque test pour éviter les contraintes UNIQUE
            $teacher = User::factory()->create();
            $teacher->assignRole('teacher');

            $student = User::factory()->create();
            $student->assignRole('student');

            $exam = Exam::factory()->create([
                'teacher_id' => $teacher->id,
                'is_active' => true,
            ]);

            $testAssignment = ExamAssignment::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => null,
                'assigned_at' => now(),
                'started_at' => now(),
            ]);

            // Act
            $response = $this->actingAs($student)->postJson(
                route('student.exams.security-violation', $exam->id),
                [
                    'violation_type' => $type,
                    'violation_details' => "Test $type",
                    'answers' => []
                ]
            );

            // Assert
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'exam_terminated' => true,
                'violation_type' => $type,
            ]);

            $testAssignment->refresh();
            $this->assertEquals('submitted', $testAssignment->status);
            $this->assertEquals($type, $testAssignment->security_violation);
            $this->assertTrue($testAssignment->forced_submission);
        }
    }

    #[Test]
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

        ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
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

    #[Test]
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
            'status' => null,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

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
