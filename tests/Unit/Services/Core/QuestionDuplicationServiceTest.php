<?php

namespace Tests\Unit\Services\Core;

use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use App\Services\Core\ChoiceManagementService;
use App\Services\Core\QuestionCrudService;
use App\Services\Core\QuestionDuplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class QuestionDuplicationServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private QuestionDuplicationService $service;

    private $classSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        $questionCrudService = new QuestionCrudService;
        $choiceManagementService = new ChoiceManagementService;

        $this->service = new QuestionDuplicationService(
            $questionCrudService,
            $choiceManagementService
        );

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

    public function test_it_duplicates_question_without_choices(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $originalQuestion */
        $originalQuestion = Question::factory()->for($originalAssessment)->create([
            'content' => 'Original question',
            'type' => 'text',
            'points' => 15,
            'order_index' => 1,
        ]);

        $duplicated = $this->service->duplicateQuestion($originalQuestion, $targetAssessment);

        $this->assertInstanceOf(Question::class, $duplicated);
        $this->assertNotEquals($originalQuestion->id, $duplicated->id);
        $this->assertEquals($originalQuestion->content, $duplicated->content);
        $this->assertEquals($originalQuestion->type, $duplicated->type);
        $this->assertEquals($originalQuestion->points, $duplicated->points);
        $this->assertEquals($targetAssessment->id, $duplicated->assessment_id);
    }

    public function test_it_duplicates_question_with_multiple_choices(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $originalQuestion */
        $originalQuestion = Question::factory()->for($originalAssessment)->create([
            'type' => 'multiple',
        ]);

        Choice::factory()->for($originalQuestion)->create([
            'content' => 'Choice A',
            'is_correct' => true,
            'order_index' => 0,
        ]);
        Choice::factory()->for($originalQuestion)->create([
            'content' => 'Choice B',
            'is_correct' => false,
            'order_index' => 1,
        ]);

        $duplicated = $this->service->duplicateQuestion($originalQuestion, $targetAssessment);

        $this->assertCount(2, $duplicated->choices);

        $duplicatedChoices = $duplicated->choices;
        $this->assertEquals('Choice A', $duplicatedChoices[0]->content);
        $this->assertTrue((bool) $duplicatedChoices[0]->is_correct);
        $this->assertEquals('Choice B', $duplicatedChoices[1]->content);
        $this->assertFalse((bool) $duplicatedChoices[1]->is_correct);
    }

    public function test_it_duplicates_boolean_question(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $originalQuestion */
        $originalQuestion = Question::factory()->for($originalAssessment)->create([
            'type' => 'boolean',
            'content' => 'Is this true?',
        ]);

        Choice::factory()->for($originalQuestion)->create(['content' => 'true', 'is_correct' => true]);
        Choice::factory()->for($originalQuestion)->create(['content' => 'false', 'is_correct' => false]);

        $duplicated = $this->service->duplicateQuestion($originalQuestion, $targetAssessment);

        $this->assertCount(2, $duplicated->choices);
        $this->assertEquals('Is this true?', $duplicated->content);
    }

    public function test_duplicated_question_has_fresh_timestamps(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $originalQuestion */
        $originalQuestion = Question::factory()->for($originalAssessment)->create();

        sleep(1);

        $duplicated = $this->service->duplicateQuestion($originalQuestion, $targetAssessment);

        $this->assertNotEquals($originalQuestion->created_at, $duplicated->created_at);
        $this->assertNotEquals($originalQuestion->updated_at, $duplicated->updated_at);
    }

    public function test_it_duplicates_multiple_questions(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $questions = Question::factory()->count(3)->for($originalAssessment)->create();

        $duplicated = $this->service->duplicateMultiple($questions, $targetAssessment);

        $this->assertCount(3, $duplicated);

        foreach ($duplicated as $index => $question) {
            $this->assertEquals($targetAssessment->id, $question->assessment_id);
            $this->assertNotEquals($questions[$index]->id, $question->id);
        }
    }

    public function test_duplicate_multiple_preserves_question_order(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $q1 = Question::factory()->for($originalAssessment)->create(['order_index' => 0, 'content' => 'Q1']);
        $q2 = Question::factory()->for($originalAssessment)->create(['order_index' => 1, 'content' => 'Q2']);
        $q3 = Question::factory()->for($originalAssessment)->create(['order_index' => 2, 'content' => 'Q3']);

        $questions = collect([$q1, $q2, $q3]);

        $duplicated = $this->service->duplicateMultiple($questions, $targetAssessment);

        $this->assertEquals(0, $duplicated[0]->order_index);
        $this->assertEquals(1, $duplicated[1]->order_index);
        $this->assertEquals(2, $duplicated[2]->order_index);
    }

    public function test_duplicate_multiple_with_choices(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $q1 = Question::factory()->for($originalAssessment)->create(['type' => 'one_choice']);
        Choice::factory()->count(2)->for($q1)->create();

        $q2 = Question::factory()->for($originalAssessment)->create(['type' => 'multiple']);
        Choice::factory()->count(4)->for($q2)->create();

        $questions = collect([$q1, $q2]);

        $duplicated = $this->service->duplicateMultiple($questions, $targetAssessment);

        $this->assertCount(2, $duplicated[0]->choices);
        $this->assertCount(4, $duplicated[1]->choices);
    }

    public function test_duplicate_multiple_handles_empty_collection(): void
    {
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $emptyCollection = collect([]);

        $duplicated = $this->service->duplicateMultiple($emptyCollection, $targetAssessment);

        $this->assertCount(0, $duplicated);
    }

    public function test_choices_are_independent_after_duplication(): void
    {
        $originalAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $targetAssessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        /** @var Question $originalQuestion */
        $originalQuestion = Question::factory()->for($originalAssessment)->create();
        $originalChoice = Choice::factory()->for($originalQuestion)->create(['content' => 'Original']);

        $duplicated = $this->service->duplicateQuestion($originalQuestion, $targetAssessment);
        $duplicatedChoice = $duplicated->choices->first();

        $originalChoice->update(['content' => 'Modified Original']);

        $this->assertEquals('Modified Original', $originalChoice->fresh()->content);
        $this->assertEquals('Original', $duplicatedChoice->fresh()->content);
    }
}
