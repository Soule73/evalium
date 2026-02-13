<?php

namespace Tests\Feature\Services\Core\Answer;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Choice;
use App\Models\Question;
use App\Models\User;
use App\Services\Core\Answer\AnswerFormatterService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnswerFormatterServiceTest extends TestCase
{
    private AnswerFormatterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AnswerFormatterService;
    }

    public function test_format_for_grading_returns_single_answer_object(): void
    {
        $assessment = Assessment::factory()->create();
        $student = User::factory()->student()->create();
        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'one_choice',
        ]);

        $choice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $answer = Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);

        $assignment->load(['answers.choice']);

        $result = $this->service->formatForGrading($assignment);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($question->id, $result);
        $this->assertEquals($answer->id, $result[$question->id]->id);
        $this->assertEquals($choice->id, $result[$question->id]->choice_id);
    }

    public function test_format_for_grading_returns_multiple_answers_with_choices_array(): void
    {
        $assessment = Assessment::factory()->create();
        $student = User::factory()->student()->create();
        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'multiple',
        ]);

        $choice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice2->id,
        ]);

        $assignment->load(['answers.choice']);

        $result = $this->service->formatForGrading($assignment);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($question->id, $result);
        $this->assertIsArray($result[$question->id]->choices);
        $this->assertCount(2, $result[$question->id]->choices);
        $this->assertEquals($choice1->id, $result[$question->id]->choices[0]['choice']->id);
        $this->assertEquals($choice2->id, $result[$question->id]->choices[1]['choice']->id);
    }

    public function test_format_for_grading_handles_text_answers(): void
    {
        $assessment = Assessment::factory()->create();
        $student = User::factory()->student()->create();
        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
        ]);

        $answer = Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer text',
            'choice_id' => null,
        ]);

        $assignment->load(['answers']);

        $result = $this->service->formatForGrading($assignment);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($question->id, $result);
        $this->assertEquals($answer->id, $result[$question->id]->id);
        $this->assertEquals('Student answer text', $result[$question->id]->answer_text);
        $this->assertNull($result[$question->id]->choice_id);
    }

    public function test_format_for_grading_uses_preloaded_answers(): void
    {
        $assessment = Assessment::factory()->create();
        $student = User::factory()->student()->create();
        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $assignment->load(['answers.choice']);

        $queryCountBefore = count(DB::getQueryLog());

        DB::enableQueryLog();

        $result = $this->service->formatForGrading($assignment);

        $queryCountAfter = count(DB::getQueryLog());

        $this->assertEquals($queryCountBefore, $queryCountAfter, 'Should not execute additional queries when answers are preloaded');
        $this->assertIsArray($result);
    }
}
