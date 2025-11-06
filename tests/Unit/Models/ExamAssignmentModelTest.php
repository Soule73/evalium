<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\ExamAssignment;
use Tests\Traits\InteractsWithTestData;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamAssignmentModelTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    #[Test]
    public function assignment_belongs_to_exam()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $this->assertInstanceOf(Exam::class, $assignment->exam);
        $this->assertEquals($exam->id, $assignment->exam->id);
    }

    #[Test]
    public function assignment_belongs_to_student()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $this->assertInstanceOf(User::class, $assignment->student);
        $this->assertEquals($student->id, $assignment->student->id);
    }

    #[Test]
    public function assignment_has_many_answers()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
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
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
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
        $exam1 = Exam::factory()->create();
        $student1 = User::factory()->create();
        $assignment1 = ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $student1->id,
            'status' => null,
            'started_at' => null,
            'submitted_at' => null,
        ]);
        $this->assertNull($assignment1->status);

        $exam2 = Exam::factory()->create();
        $student2 = User::factory()->create();
        $assignment2 = ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $student2->id,
            'status' => 'submitted',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $this->assertEquals('submitted', $assignment2->status);

        $exam3 = Exam::factory()->create();
        $student3 = User::factory()->create();
        $assignment3 = ExamAssignment::factory()->create([
            'exam_id' => $exam3->id,
            'student_id' => $student3->id,
            'status' => 'graded',
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'score' => 15.5,
        ]);
        $this->assertEquals('graded', $assignment3->status);
    }

    #[Test]
    public function assignment_has_default_status()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $this->assertNull($assignment->status);
    }

    #[Test]
    public function assignment_can_calculate_duration()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $startTime = Carbon::parse('2025-01-01 10:00:00');
        $endTime = Carbon::parse('2025-01-01 11:30:00');

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
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
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
            'started_at' => null
        ]);

        $assignment->update([
            'started_at' => Carbon::now()
        ]);

        $this->assertNull($assignment->status);
        $this->assertNotNull($assignment->started_at);
    }

    #[Test]
    public function assignment_can_be_submitted()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
            'started_at' => Carbon::now()->subHour()
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
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'score' => 85.5,
            'auto_score' => 78.0
        ]);

        $this->assertEquals(85.5, $assignment->score);
        $this->assertEquals(78.0, $assignment->auto_score);
    }

    #[Test]
    public function assignment_prevents_duplicate_exam_student_pairs()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);
    }

    #[Test]
    public function assignment_can_have_teacher_notes()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'teacher_notes' => 'Excellent work, but could improve on question 3'
        ]);

        $this->assertEquals('Excellent work, but could improve on question 3', $assignment->teacher_notes);
    }

    #[Test]
    public function assignment_can_track_security_violations()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'security_violation' => 'full_screen_exit'
        ]);

        $this->assertEquals('full_screen_exit', $assignment->security_violation);
    }

    #[Test]
    public function assignment_can_be_forced_submission()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'forced_submission' => true
        ]);

        $this->assertTrue($assignment->forced_submission);
    }

    #[Test]
    public function assignment_has_default_security_values()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create();

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $this->assertNull($assignment->security_violation);
        $this->assertFalse($assignment->forced_submission);
    }
}
