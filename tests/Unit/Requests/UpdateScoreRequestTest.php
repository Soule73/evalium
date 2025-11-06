<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Exam\UpdateScoreRequest;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class UpdateScoreRequestTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('exam_id'));
        $this->assertTrue($validator->errors()->has('student_id'));
        $this->assertTrue($validator->errors()->has('question_id'));
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_score_is_numeric()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'type' => 'text', 'points' => 10]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'score' => 'not-a-number',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_score_minimum()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'type' => 'text', 'points' => 10]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'score' => -1,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_question_exists()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => 999,
            'score' => 8.5,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('question_id'));
    }

    #[Test]
    public function it_validates_teacher_notes_string()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'type' => 'text', 'points' => 10]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'score' => 8.5,
            'feedback' => ['not', 'a', 'string'],
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('feedback'));
    }

    #[Test]
    public function it_passes_validation_with_valid_data()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'type' => 'text', 'points' => 10]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);

        \App\Models\Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Some answer',
        ]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'score' => 8.5,
            'feedback' => 'Good answer but could be improved',
        ], $rules);

        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_validation_without_optional_teacher_notes()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'type' => 'text', 'points' => 10]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);

        \App\Models\Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Some answer',
        ]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'score' => 8.5,
        ], $rules);

        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_score_against_question_points()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $questionWith5Points = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 5,
        ]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);

        \App\Models\Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $questionWith5Points->id,
            'answer_text' => 'Some answer',
        ]);

        $request = new UpdateScoreRequest;
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $questionWith5Points->id,
            'score' => 10,
        ], $rules);

        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }
}
