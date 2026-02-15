<?php

namespace Tests\Unit\Services\Core;

use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use App\Services\Core\QuestionCrudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class QuestionCrudServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private QuestionCrudService $service;

    private $classSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new QuestionCrudService;

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

    public function test_it_can_create_single_question(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $questionData = [
            'content' => 'What is 2+2?',
            'type' => 'one_choice',
            'points' => 10,
            'order_index' => 1,
        ];

        $question = $this->service->createQuestion($assessment, $questionData);

        $this->assertInstanceOf(Question::class, $question);
        $this->assertEquals('What is 2+2?', $question->content);
        $this->assertEquals(QuestionType::OneChoice, $question->type);
        $this->assertEquals(10, $question->points);
        $this->assertEquals(1, $question->order_index);
        $this->assertEquals($assessment->id, $question->assessment_id);
    }

    public function test_it_can_create_multiple_questions_for_assessment(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $questionsData = [
            [
                'content' => 'Question 1',
                'type' => 'multiple',
                'points' => 5,
                'order_index' => 0,
            ],
            [
                'content' => 'Question 2',
                'type' => 'one_choice',
                'points' => 10,
                'order_index' => 1,
            ],
        ];

        $questions = $this->service->createQuestionsForAssessment($assessment, $questionsData);

        $this->assertCount(2, $questions);
        $this->assertEquals('Question 1', $questions[0]->content);
        $this->assertEquals('Question 2', $questions[1]->content);
    }

    public function test_it_can_update_question(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        /** @var Question $question */
        $question = Question::factory()->for($assessment)->create([
            'content' => 'Original content',
            'points' => 5,
        ]);

        $updatedData = [
            'content' => 'Updated content',
            'type' => $question->type,
            'points' => 15,
            'order_index' => 2,
        ];

        $updated = $this->service->updateQuestion($question, $updatedData);

        $this->assertEquals('Updated content', $updated->content);
        $this->assertEquals(15, $updated->points);
        $this->assertEquals(2, $updated->order_index);
    }

    public function test_it_can_update_question_by_id(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $question = Question::factory()->for($assessment)->create([
            'content' => 'Old content',
        ]);

        $updatedData = [
            'content' => 'New content',
            'type' => 'one_choice',
            'points' => 20,
            'order_index' => 0,
        ];

        $updated = $this->service->updateQuestionById($assessment, $question->id, $updatedData);

        $this->assertNotNull($updated);
        $this->assertEquals('New content', $updated->content);
        $this->assertEquals(20, $updated->points);
    }

    public function test_update_question_by_id_returns_null_for_different_assessment(): void
    {
        $assessment1 = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $assessment2 = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $question = Question::factory()->for($assessment2)->create([
            'content' => 'Original content',
            'points' => 5,
        ]);

        $updatedData = [
            'content' => 'Should not update',
            'type' => 'one_choice',
            'points' => 20,
            'order_index' => 0,
        ];

        $result = $this->service->updateQuestionById($assessment1, $question->id, $updatedData);

        $this->assertNull($result);
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'content' => 'Original content',
            'points' => 5,
        ]);
    }

    public function test_it_can_delete_single_question(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        /** @var Question $question */
        $question = Question::factory()->for($assessment)->create();
        Choice::factory()->count(3)->for($question)->create();

        $this->service->deleteQuestion($question);

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('choices', ['question_id' => $question->id]);
        $this->assertDatabaseMissing('answers', ['question_id' => $question->id]);
    }

    public function test_it_can_delete_questions_by_ids(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $questions = Question::factory()->count(3)->for($assessment)->create();

        $questionIds = $questions->pluck('id')->toArray();

        $this->service->deleteQuestionsById($assessment, $questionIds);

        foreach ($questionIds as $id) {
            $this->assertDatabaseMissing('questions', ['id' => $id]);
        }
    }

    public function test_it_only_deletes_questions_belonging_to_assessment(): void
    {
        $assessment1 = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $assessment2 = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $question1 = Question::factory()->for($assessment1)->create();
        $question2 = Question::factory()->for($assessment2)->create();

        $this->service->deleteQuestionsById($assessment1, [$question1->id, $question2->id]);

        $this->assertDatabaseMissing('questions', ['id' => $question1->id]);
        $this->assertDatabaseHas('questions', ['id' => $question2->id]);
    }

    public function test_it_can_delete_questions_in_bulk(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $questions = Question::factory()->count(5)->for($assessment)->create();
        $questionIds = $questions->pluck('id')->toArray();

        $this->service->deleteBulk($questionIds);

        foreach ($questionIds as $id) {
            $this->assertDatabaseMissing('questions', ['id' => $id]);
        }
    }

    public function test_delete_bulk_handles_collection(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);
        $questions = Question::factory()->count(3)->for($assessment)->create();
        $questionIds = $questions->pluck('id');

        $this->service->deleteBulk($questionIds);

        foreach ($questionIds as $id) {
            $this->assertDatabaseMissing('questions', ['id' => $id]);
        }
    }

    public function test_delete_bulk_handles_empty_array(): void
    {
        $this->service->deleteBulk([]);

        $this->assertTrue(true);
    }

    public function test_create_question_sets_default_order_index(): void
    {
        $assessment = Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $questionData = [
            'content' => 'Test question',
            'type' => 'text',
            'points' => 5,
        ];

        $question = $this->service->createQuestion($assessment, $questionData);

        $this->assertEquals(0, $question->order_index);
    }
}
