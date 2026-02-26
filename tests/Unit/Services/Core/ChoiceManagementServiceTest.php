<?php

namespace Tests\Unit\Services\Core;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Services\Core\ChoiceManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ChoiceManagementServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ChoiceManagementService $service;

    private $classSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new ChoiceManagementService;

        $teacher = $this->createTeacher();

        $academicYear = \App\Models\AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30']
        );

        $semester = \App\Models\Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'order_number' => 1],
            ['name' => 'Semester 1', 'start_date' => '2023-09-01', 'end_date' => '2024-01-31']
        );

        $class = \App\Models\ClassModel::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'Test Class'],
            ['level_id' => \App\Models\Level::factory()->create()->id]
        );

        $subject = \App\Models\Subject::firstOrCreate(
            ['name' => 'Test Subject'],
            ['code' => 'TST', 'level_id' => $class->level_id]
        );

        $this->classSubject = \App\Models\ClassSubject::firstOrCreate([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ], ['coefficient' => 1, 'valid_from' => now()]);
    }

    private function createQuestionForTest(array $attributes = []): Question
    {
        $assessment = \App\Models\Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $question */
        $question = Question::factory()->for($assessment)->create($attributes);

        return $question;
    }

    public function test_it_creates_multiple_choice_options(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);

        $questionData = [
            'type' => 'multiple',
            'choices' => [
                ['content' => 'Option A', 'is_correct' => true, 'order_index' => 0],
                ['content' => 'Option B', 'is_correct' => true, 'order_index' => 1],
                ['content' => 'Option C', 'is_correct' => false, 'order_index' => 2],
            ],
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 3);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'Option A',
            'is_correct' => true,
        ]);
    }

    public function test_it_creates_boolean_options(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'boolean']);

        $questionData = [
            'type' => 'boolean',
            'choices' => [
                ['content' => 'true', 'is_correct' => true],
            ],
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 2);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'true',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'false',
            'is_correct' => false,
        ]);
    }

    public function test_it_creates_boolean_with_false_as_correct(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'boolean']);

        $questionData = [
            'type' => 'boolean',
            'choices' => [
                ['content' => 'false', 'is_correct' => true],
            ],
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'false',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'true',
            'is_correct' => false,
        ]);
    }

    public function test_it_does_not_create_choices_for_text_questions(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'text']);

        $questionData = [
            'type' => 'text',
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 0);
    }

    public function test_it_updates_multiple_choice_options(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);
        $existingChoice = Choice::factory()->for($question)->create([
            'content' => 'Old content',
            'is_correct' => false,
        ]);

        $questionData = [
            'type' => 'multiple',
            'choices' => [
                [
                    'id' => $existingChoice->id,
                    'content' => 'Updated content',
                    'is_correct' => true,
                    'order_index' => 0,
                ],
                ['content' => 'New option', 'is_correct' => false, 'order_index' => 1],
            ],
        ];

        $this->service->updateChoicesForQuestion($question, $questionData);

        $this->assertDatabaseHas('choices', [
            'id' => $existingChoice->id,
            'content' => 'Updated content',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'New option',
        ]);
    }

    public function test_it_updates_boolean_options(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'boolean']);
        Choice::factory()->for($question)->create(['content' => 'true', 'is_correct' => true]);
        Choice::factory()->for($question)->create(['content' => 'false', 'is_correct' => false]);

        $questionData = [
            'type' => 'boolean',
            'choices' => [
                ['content' => 'false', 'is_correct' => true],
            ],
        ];

        $this->service->updateChoicesForQuestion($question, $questionData);

        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'false',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'content' => 'true',
            'is_correct' => false,
        ]);
    }

    public function test_it_deletes_all_choices_when_type_changes_to_text(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);
        Choice::factory()->count(3)->for($question)->create();

        $questionData = [
            'type' => 'text',
        ];

        $this->service->updateChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 0);
    }

    public function test_it_deletes_choices_by_ids(): void
    {
        $question = $this->createQuestionForTest();
        $choices = Choice::factory()->count(3)->for($question)->create();
        $choiceIds = $choices->pluck('id')->toArray();

        $this->service->deleteChoicesByIds($choiceIds);

        foreach ($choiceIds as $id) {
            $this->assertDatabaseMissing('choices', ['id' => $id]);
        }
    }

    public function test_it_deletes_related_answers_when_deleting_choices(): void
    {
        $question = $this->createQuestionForTest();
        $choice = Choice::factory()->for($question)->create();

        $student = $this->createStudent();
        $assignment = $this->createAssignmentForStudent($question->assessment, $student);
        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'choice_id' => $choice->id,
            'assessment_assignment_id' => $assignment->id,
        ]);

        $this->service->deleteChoicesByIds([$choice->id]);

        $this->assertDatabaseMissing('choices', ['id' => $choice->id]);
        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
    }

    public function test_it_deletes_all_choices_for_question(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest();
        Choice::factory()->count(4)->for($question)->create();

        $this->service->deleteAllChoices($question);

        $this->assertDatabaseMissing('choices', ['question_id' => $question->id]);
    }

    public function test_delete_all_choices_also_deletes_related_answers(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest();
        $choice = Choice::factory()->for($question)->create();

        $student = $this->createStudent();
        $assignment = $this->createAssignmentForStudent($question->assessment, $student);
        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'choice_id' => $choice->id,
            'assessment_assignment_id' => $assignment->id,
        ]);

        $this->service->deleteAllChoices($question);

        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
    }

    public function test_it_handles_empty_choices_array(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);

        $questionData = [
            'type' => 'multiple',
            'choices' => [],
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 0);
    }

    public function test_it_handles_missing_choices_key(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);

        $questionData = [
            'type' => 'one_choice',
        ];

        $this->service->createChoicesForQuestion($question, $questionData);

        $this->assertDatabaseCount('choices', 0);
    }

    public function test_boolean_removes_non_standard_choices(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'boolean']);
        Choice::factory()->for($question)->create(['content' => 'true']);
        Choice::factory()->for($question)->create(['content' => 'false']);
        Choice::factory()->for($question)->create(['content' => 'maybe']);

        $questionData = [
            'type' => 'boolean',
            'choices' => [
                ['content' => 'true', 'is_correct' => true],
            ],
        ];

        $this->service->updateChoicesForQuestion($question, $questionData);

        $this->assertDatabaseMissing('choices', ['content' => 'maybe']);
        $this->assertDatabaseCount('choices', 2);
    }

    public function test_update_multiple_choice_removes_orphaned_choices(): void
    {
        /** @var Question $question */
        $question = $this->createQuestionForTest(['type' => 'multiple']);

        $choiceA = Choice::factory()->for($question)->create(['content' => 'Option A', 'is_correct' => true]);
        $choiceB = Choice::factory()->for($question)->create(['content' => 'Option B', 'is_correct' => false]);
        $choiceC = Choice::factory()->for($question)->create(['content' => 'Option C', 'is_correct' => false]);

        $questionData = [
            'type' => 'multiple',
            'choices' => [
                ['id' => $choiceA->id, 'content' => 'Option A updated', 'is_correct' => true, 'order_index' => 0],
                ['id' => $choiceB->id, 'content' => 'Option B', 'is_correct' => true, 'order_index' => 1],
            ],
        ];

        $this->service->updateChoicesForQuestion($question, $questionData);

        $this->assertDatabaseMissing('choices', ['id' => $choiceC->id]);
        $this->assertDatabaseHas('choices', ['id' => $choiceA->id, 'content' => 'Option A updated']);
        $this->assertDatabaseHas('choices', ['id' => $choiceB->id, 'is_correct' => true]);
        $this->assertDatabaseCount('choices', 2);
    }
}
