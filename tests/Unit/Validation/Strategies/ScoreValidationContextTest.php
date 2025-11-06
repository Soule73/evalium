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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScoreValidationContextTest extends TestCase
{
    use RefreshDatabase;

    private ScoreValidationContext $context;
    private Exam $exam;
    private User $teacher;
    private User $student;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new ScoreValidationContext();

        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->exam = $exam;

        /** @var Question $question */
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'points' => 10
        ]);
        $this->question = $question;
    }

    public function test_question_exists_in_exam_validation_strategy_passes(): void
    {
        $data = [
            'scores' => [
                [
                    'question_id' => $this->question->id,
                    'score' => 5
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam'],
            ['exam' => $this->exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
    }

    public function test_question_exists_in_exam_validation_strategy_fails(): void
    {
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
            ['exam' => $this->exam]
        );

        $this->assertTrue($validator->errors()->has('scores.0.question_id'));
    }

    public function test_score_not_exceeds_max_validation_strategy_passes(): void
    {
        $data = [
            'scores' => [
                [
                    'question_id' => $this->question->id,
                    'score' => 8
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['exam' => $this->exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_score_not_exceeds_max_validation_strategy_fails(): void
    {
        $data = [
            'scores' => [
                [
                    'question_id' => $this->question->id,
                    'score' => 15
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['exam' => $this->exam]
        );

        $this->assertTrue($validator->errors()->has('scores.0.score'));
    }

    public function test_single_question_exists_validation_strategy_passes(): void
    {
        $data = [
            'exam_id' => $this->exam->id,
            'question_id' => $this->question->id,
            'score' => 8
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertFalse($validator->errors()->has('question_id'));
        $this->assertFalse($validator->errors()->has('score'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_nonexistent_question(): void
    {
        $data = [
            'exam_id' => $this->exam->id,
            'question_id' => 99999,
            'score' => 8
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('question_id'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_exceeded_score(): void
    {
        $data = [
            'exam_id' => $this->exam->id,
            'question_id' => $this->question->id,
            'score' => 15
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('score'));
    }

    public function test_student_assignment_validation_strategy_passes(): void
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $this->question->id
        ]);

        $data = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertFalse($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_for_non_student(): void
    {
        $data = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->teacher->id,
            'question_id' => $this->question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_not_assigned(): void
    {
        $data = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_no_answer(): void
    {
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id
        ]);

        $data = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_multiple_validation_strategies_at_once(): void
    {
        $data = [
            'scores' => [
                [
                    'question_id' => $this->question->id,
                    'score' => 8
                ]
            ]
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam', 'score_not_exceeds_max'],
            ['exam' => $this->exam]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_handles_empty_scores_array_gracefully(): void
    {
        $data = ['scores' => []];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_exam', 'score_not_exceeds_max'],
            ['exam' => $this->exam]
        );

        $this->assertFalse($validator->errors()->any());
    }
}
