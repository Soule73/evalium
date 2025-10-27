<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\ExamAssignment;
use Tests\Traits\CreatesTestRoles;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamAssignmentModelTest extends TestCase
{
    use RefreshDatabase, CreatesTestRoles;

    private User $student;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestRoles();
        $this->student = $this->createUserWithRole('student', [
            'email' => 'student@test.com',
        ]);

        $this->exam = Exam::factory()->create();
    }

    #[Test]
    public function assignment_belongs_to_exam()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $this->assertInstanceOf(Exam::class, $assignment->exam);
        $this->assertEquals($this->exam->id, $assignment->exam->id);
    }

    #[Test]
    public function assignment_belongs_to_student()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $this->assertInstanceOf(User::class, $assignment->student);
        $this->assertEquals($this->student->id, $assignment->student->id);
    }

    #[Test]
    public function assignment_has_many_answers()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $answers = Answer::factory()->count(3)->create([
            'assignment_id' => $assignment->id
        ]);

        $this->assertCount(3, $assignment->answers);
        $this->assertEquals($answers->first()->id, $assignment->answers->first()->id);
    }

    #[Test]
    public function assignment_has_correct_fillable_attributes()
    {
        $fillable = (new ExamAssignment())->getFillable();

        $expectedFillable = [
            'exam_id',
            'student_id',
            'assigned_at',
            'started_at',
            'submitted_at',
            'score',
            'auto_score',
            'status',
            'teacher_notes',
            'security_violation',
            'forced_submission',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function assignment_casts_timestamps_correctly()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'assigned_at' => '2025-01-01 10:00:00',
            'started_at' => '2025-01-01 10:30:00',
            'submitted_at' => '2025-01-01 12:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $assignment->assigned_at);
        $this->assertInstanceOf(Carbon::class, $assignment->started_at);
        $this->assertInstanceOf(Carbon::class, $assignment->submitted_at);
    }

    #[Test]
    public function assignment_has_valid_status_values()
    {
        $validStatuses = ['assigned', 'started', 'submitted', 'graded'];

        foreach ($validStatuses as $index => $status) {
            $exam = Exam::factory()->create();
            $student = User::factory()->create();

            $assignment = ExamAssignment::factory()->create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => $status
            ]);

            $this->assertEquals($status, $assignment->status);
        }
    }

    #[Test]
    public function assignment_has_default_status()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $this->assertEquals('assigned', $assignment->status);
    }

    #[Test]
    public function assignment_can_calculate_duration()
    {
        $startTime = Carbon::parse('2025-01-01 10:00:00');
        $endTime = Carbon::parse('2025-01-01 11:30:00');

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'started_at' => $startTime,
            'submitted_at' => $endTime
        ]);

        if ($assignment->started_at && $assignment->submitted_at) {
            $duration = $assignment->started_at->diffInMinutes($assignment->submitted_at);
            $this->assertEquals(90, $duration);
        }
    }

    #[Test]
    public function assignment_can_be_marked_as_started()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);

        $assignment->update([
            'status' => 'started',
            'started_at' => Carbon::now()
        ]);

        $this->assertEquals('started', $assignment->status);
        $this->assertNotNull($assignment->started_at);
    }

    #[Test]
    public function assignment_can_be_submitted()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started'
        ]);

        $assignment->update([
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);

        $this->assertEquals('submitted', $assignment->status);
        $this->assertNotNull($assignment->submitted_at);
    }

    #[Test]
    public function assignment_can_have_both_manual_and_auto_scores()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'score' => 85.5,
            'auto_score' => 78.0
        ]);

        $this->assertEquals(85.5, $assignment->score);
        $this->assertEquals(78.0, $assignment->auto_score);
    }

    #[Test]
    public function assignment_prevents_duplicate_exam_student_pairs()
    {
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);
    }

    #[Test]
    public function assignment_can_have_teacher_notes()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'teacher_notes' => 'Excellent work, but could improve on question 3'
        ]);

        $this->assertEquals('Excellent work, but could improve on question 3', $assignment->teacher_notes);
    }

    #[Test]
    public function assignment_can_track_security_violations()
    {

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'security_violation' => 'full_screen_exit'
        ]);

        $this->assertEquals('full_screen_exit', $assignment->security_violation);
    }

    #[Test]
    public function assignment_can_be_forced_submission()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'forced_submission' => true
        ]);

        $this->assertTrue($assignment->forced_submission);
    }

    #[Test]
    public function assignment_has_default_security_values()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $this->assertNull($assignment->security_violation);
        $this->assertFalse($assignment->forced_submission);
    }
}
