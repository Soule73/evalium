<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;
use Tests\Traits\CreatesTestRoles;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Shared\UserAnswerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAnswerServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestRoles;

    private UserAnswerService $service;
    private User $student;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestRoles();
        $this->service = new UserAnswerService();

        // Créer un étudiant
        $this->student = $this->createUserWithRole('student', [
            'email' => 'student@test.com',
        ]);

        // Créer un examen
        $this->exam = Exam::factory()->create([
            'title' => 'Test Exam'
        ]);

        // Créer une assignation
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'score' => 85.5
        ]);
    }

    #[Test]
    public function it_can_get_student_results_data()
    {
        // Créer une question et une réponse
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'content' => 'Test question',
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Test answer',
            'score' => 8.5
        ]);

        $result = $this->service->getStudentResultsData($this->assignment);

        $this->assertArrayHasKey('assignment', $result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('formattedAnswers', $result);

        $this->assertEquals($this->assignment->id, $result['assignment']->id);
        $this->assertEquals($this->student->id, $result['student']->id);
        $this->assertIsArray($result['formattedAnswers']);
    }

    #[Test]
    public function it_can_get_student_review_data()
    {
        $result = $this->service->getStudentReviewData($this->assignment);

        $this->assertArrayHasKey('assignment', $result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertArrayHasKey('userAnswers', $result);
        $this->assertArrayHasKey('totalQuestions', $result);
        $this->assertArrayHasKey('totalPoints', $result);
    }

    #[Test]
    public function it_can_format_user_answers_for_frontend()
    {
        // Créer différents types de questions
        $textQuestion = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'content' => 'Text question',
            'points' => 5
        ]);

        $multipleQuestion = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'content' => 'Multiple choice question',
            'points' => 3
        ]);

        // Créer des choix pour la question multiple
        $choice1 = Choice::factory()->create([
            'question_id' => $multipleQuestion->id,
            'content' => 'Choice 1',
            'is_correct' => true
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $multipleQuestion->id,
            'content' => 'Choice 2',
            'is_correct' => false
        ]);

        // Utiliser l'assignment existant créé dans setUp()

        // Créer des réponses
        $textAnswer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $textQuestion->id,
            'answer_text' => 'Text answer',
            'score' => 4.5
        ]);

        $multipleAnswer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $multipleQuestion->id,
            'choice_id' => $choice1->id,
            'score' => 3.0
        ]);

        $answers = collect([$textAnswer, $multipleAnswer]);
        $result = $this->service->formatUserAnswersForFrontend($this->assignment);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Vérifier la structure de la réponse text
        $this->assertEquals($textQuestion->id, $result[0]['question_id']);
        $this->assertEquals('Text answer', $result[0]['answer_text']);
        $this->assertEquals(4.5, $result[0]['score']);

        // Vérifier la structure de la réponse multiple
        $this->assertEquals($multipleQuestion->id, $result[1]['question_id']);
        $this->assertEquals($choice1->id, $result[1]['choice_id']);
        $this->assertEquals(3.0, $result[1]['score']);
    }

    #[Test]
    public function it_handles_answers_without_choices()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text'
        ]);

        // Utiliser l'assignment existant créé dans setUp() avec un nouvel étudiant
        $newStudent = User::factory()->create([
            'email' => 'student2@test.com',
        ]);
        $newStudent->assignRole('student');

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $newStudent->id
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Simple text answer',
            'choice_id' => null
        ]);

        $answers = collect([$answer]);
        $result = $this->service->formatUserAnswersForFrontend($assignment);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]['choice_id']);
        $this->assertEquals('Simple text answer', $result[0]['answer_text']);
    }

    #[Test]
    public function it_handles_empty_answers_collection()
    {
        // Utiliser l'assignment existant créé dans setUp() avec un autre étudiant
        $newStudent = User::factory()->create([
            'email' => 'student3@test.com',
        ]);
        $newStudent->assignRole('student');

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $newStudent->id
        ]);

        $result = $this->service->formatUserAnswersForFrontend($assignment);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
