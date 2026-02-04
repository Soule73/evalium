<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Strategies;

use App\Models\Answer;
use App\Models\AssessmentAssignment;
use App\Models\Question;
use App\Strategies\Validation\Score\ScoreValidationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ScoreValidationContextTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ScoreValidationContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->context = new ScoreValidationContext;
    }

    public function test_question_exists_in_exam_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 5,
                ],
            ],
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_assessment'],
            ['assessment' => $assessment]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
    }

    public function test_question_exists_in_exam_validation_strategy_fails(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);

        $data = [
            'scores' => [
                [
                    'question_id' => 99999,
                    'score' => 5,
                ],
            ],
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_assessment'],
            ['assessment' => $assessment]
        );

        $this->assertTrue($validator->errors()->has('scores.0.question_id'));
    }

    public function test_score_not_exceeds_max_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 8,
                ],
            ],
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['assessment' => $assessment]
        );

        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_score_not_exceeds_max_validation_strategy_fails(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 15,
                ],
            ],
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['score_not_exceeds_max'],
            ['assessment' => $assessment]
        );

        $this->assertTrue($validator->errors()->has('scores.0.score'));
    }

    public function test_single_question_exists_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'score' => 8,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertFalse($validator->errors()->has('question_id'));
        $this->assertFalse($validator->errors()->has('score'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_nonexistent_question(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);

        $data = [
            'assessment_id' => $assessment->id,
            'question_id' => 99999,
            'score' => 8,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('question_id'));
    }

    public function test_single_question_exists_validation_strategy_fails_for_exceeded_score(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'score' => 15,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['single_question_exists']);

        $this->assertTrue($validator->errors()->has('score'));
    }

    public function test_student_assignment_validation_strategy_passes(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $data = [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertFalse($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_for_non_student(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'assessment_id' => $assessment->id,
            'student_id' => $teacher->id,
            'question_id' => $question->id,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_not_assigned(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_student_assignment_validation_strategy_fails_when_no_answer(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $data = [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
        ];

        $validator = Validator::make($data, []);
        $this->context->validate($validator, $data, ['student_assignment']);

        $this->assertTrue($validator->errors()->has('student_id'));
    }

    public function test_multiple_validation_strategies_at_once(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 10]);

        $data = [
            'scores' => [
                [
                    'question_id' => $question->id,
                    'score' => 8,
                ],
            ],
        ];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_assessment', 'score_not_exceeds_max'],
            ['assessment' => $assessment]
        );

        $this->assertFalse($validator->errors()->has('scores.0.question_id'));
        $this->assertFalse($validator->errors()->has('scores.0.score'));
    }

    public function test_handles_empty_scores_array_gracefully(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $assessment = $this->createAssessmentWithQuestions($teacher, [], 0);

        $data = ['scores' => []];

        $validator = Validator::make($data, []);
        $this->context->validate(
            $validator,
            $data,
            ['question_exists_in_assessment', 'score_not_exceeds_max'],
            ['assessment' => $assessment]
        );

        $this->assertFalse($validator->errors()->any());
    }
}
