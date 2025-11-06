<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use App\Models\Answer;
use App\Models\ExamAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTestData;

class ExamModelTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    #[Test]
    public function exam_belongs_to_teacher()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $this->assertInstanceOf(User::class, $exam->teacher);
        $this->assertEquals($teacher->id, $exam->teacher->id);
    }

    #[Test]
    public function exam_has_many_questions()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $questions = Question::factory()->count(3)->create([
            'exam_id' => $exam->id
        ]);

        $this->assertCount(3, $exam->questions);
        $this->assertInstanceOf(Question::class, $exam->questions->first());
    }

    #[Test]
    public function exam_has_many_assignments()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $assignments = ExamAssignment::factory()->count(2)->create([
            'exam_id' => $exam->id
        ]);

        $this->assertCount(2, $exam->assignments);
        $this->assertInstanceOf(ExamAssignment::class, $exam->assignments->first());
    }

    #[Test]
    public function exam_calculates_total_points()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        Question::factory()->create([
            'exam_id' => $exam->id,
            'points' => 5
        ]);

        Question::factory()->create([
            'exam_id' => $exam->id,
            'points' => 10
        ]);

        $exam->refresh();
        $this->assertEquals(15, $exam->total_points);
    }

    #[Test]
    public function exam_counts_unique_participants()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $student1 = User::factory()->create();
        $student2 = User::factory()->create();

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student2->id
        ]);

        $this->assertEquals(2, $exam->unique_participants_count);
    }

    #[Test]
    public function exam_has_correct_fillable_attributes()
    {
        $fillable = (new Exam())->getFillable();

        $expectedFillable = [
            'title',
            'description',
            'duration',
            'start_time',
            'end_time',
            'is_active',
            'teacher_id',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function exam_casts_attributes_correctly()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'start_time' => '2025-01-01 10:00:00',
            'end_time' => '2025-01-01 12:00:00',
            'is_active' => 1
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $exam->start_time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $exam->end_time);
        $this->assertIsBool($exam->is_active);
        $this->assertTrue($exam->is_active);
    }

    #[Test]
    public function exam_can_determine_if_active()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $activeExam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true
        ]);

        $inactiveExam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => false
        ]);

        $this->assertTrue($activeExam->is_active);
        $this->assertFalse($inactiveExam->is_active);
    }

    #[Test]
    public function exam_has_answers_through_questions()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id
        ]);

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => User::factory()->create()->id
        ]);

        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'assignment_id' => $assignment->id
        ]);

        $this->assertCount(1, $exam->answers);
        $this->assertEquals($answer->id, $exam->answers->first()->id);
    }

    #[Test]
    public function exam_questions_are_ordered_by_order_index()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $question3 = Question::factory()->create([
            'exam_id' => $exam->id,
            'order_index' => 3,
            'content' => 'Third question'
        ]);

        $question1 = Question::factory()->create([
            'exam_id' => $exam->id,
            'order_index' => 1,
            'content' => 'First question'
        ]);

        $question2 = Question::factory()->create([
            'exam_id' => $exam->id,
            'order_index' => 2,
            'content' => 'Second question'
        ]);

        $orderedQuestions = $exam->questions;

        $this->assertEquals('First question', $orderedQuestions[0]->content);
        $this->assertEquals('Second question', $orderedQuestions[1]->content);
        $this->assertEquals('Third question', $orderedQuestions[2]->content);
    }
}
