<?php

namespace Tests\Feature\Controllers\Student;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamAssignment;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\Traits\InteractsWithTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamSecurityViolationTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createExamWithAssignment(): array
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
            'duration' => 60,
        ]);

        Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple',
            'points' => 10,
        ]);

        $assignment = ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        return compact('teacher', 'student', 'exam', 'assignment');
    }

    #[Test]
    public function security_violation_changes_status_to_submitted()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Student switched tabs',
                'answers' => []
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'exam_terminated' => true,
            'violation_type' => 'tab_switch',
        ]);

        $this->assertDatabaseHas('exam_assignments', [
            'id' => $assignment->id,
            'status' => 'submitted',
            'security_violation' => 'tab_switch',
            'forced_submission' => true,
        ]);

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertEquals('tab_switch', $assignment->security_violation);
        $this->assertTrue($assignment->forced_submission);
        $this->assertNotNull($assignment->submitted_at);
    }

    #[Test]
    public function security_violation_saves_auto_score()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'fullscreen_exit',
                'violation_details' => 'Student exited fullscreen',
                'answers' => []
            ]
        );

        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertNotNull($assignment->auto_score);
        $this->assertNotNull($assignment->submitted_at);
    }

    #[Test]
    public function security_violation_records_violation_type()
    {
        $violationTypes = ['tab_switch', 'fullscreen_exit', 'browser_change', 'copy_paste'];

        foreach ($violationTypes as $type) {
            $teacher = $this->createTeacher();
            $student = $this->createStudent();

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

            $response = $this->actingAs($student)->postJson(
                route('student.exams.security-violation', $exam->id),
                [
                    'violation_type' => $type,
                    'violation_details' => "Test $type",
                    'answers' => []
                ]
            );

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
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

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

        $response = $this->actingAs($student)->postJson(
            route('student.exams.security-violation', $exam->id),
            [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Test',
                'answers' => []
            ]
        );

        $response->assertStatus(404);
    }

    #[Test]
    public function security_violation_saves_student_answers()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

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

        $response->assertStatus(200);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'My answer before violation',
        ]);
    }
}
