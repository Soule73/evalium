<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Strategies;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Question;
use App\Models\User;
use App\Strategies\Validation\Score\ScoreValidationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ScoreValidationContextTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    private ScoreValidationContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->context = new ScoreValidationContext();
    }

    public function test_question_exists_in_exam_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 5
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam'],
            ['exam' => $exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
    }

    public function test_question_exists_in_exam_validation_strategy_fails(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $data = [
            'scores' => [
                [
                    'question_id' => 99999,
                    'score' => 5
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam'],
            ['exam' => $exam]
        );

        $this->assertTrue($validator->errors()->has('scores.0.question_id'));
    }

    public function test_score_not_exceeds_max_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 8
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['exam' => $exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_score_not_exceeds_max_validation_strategy_fails(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 15
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['exam' => $exam]
        );

        $this->assertTrue($validator->errors()->has('scores.0.score'));
    }

    public function test_single_question_exists_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'score' => 8
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertFalse($validator->errors()->has('question_id'));
        $this->assertFalse($validator->errors()->has('score'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_nonexistent_question(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $data = [
            'exam_id' => $exam->id,
            'question_id' => 99999,
            'score' => 8
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('question_id'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_exceeded_score(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'score' => 15
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('score'));
    }

    public function test_student_assignment_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id
        ]);

        $data = [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertFalse($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_for_non_student(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'exam_id' => $exam->id,
            'student_id' => $teacher->id,
            'question_id' => $question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_not_assigned(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_no_answer(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id
        ]);

        $data = [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'question_id' => $question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_multiple_validation_strategies_at_once(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 8
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam', 'score_not_exceeds_max'],
            ['exam' => $exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_handles_empty_scores_array_gracefully(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $data = ['scores' => []];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam', 'score_not_exceeds_max'],
            ['exam' => $exam]
        );

        $this->assertFalse($validator->errors()->any());
    }
}
