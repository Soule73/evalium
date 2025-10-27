<?php

namespace Tests\Unit\Services\Core\Scoring;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesTestRoles;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestRoles;

    private ScoringService $scoringService;
    private User $student;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles
        $this->createTestRoles();

        // Obtenir le service depuis le container
        $this->scoringService = app(ScoringService::class);

        // Créer un étudiant
        $this->student = $this->createUserWithRole('student', [
            'email' => 'student@test.com',
        ]);

        // Créer un examen
        $this->exam = Exam::factory()->create([
            'title' => 'Test Exam for Scoring',
            'duration' => 60,
        ]);

        // Créer une assignation
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    #[Test]
    public function it_calculates_score_for_correct_one_choice_question(): void
    {
        // Créer une question à choix unique
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        // Créer les choix
        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        // Créer une réponse correcte
        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
        ]);

        // Calculer le score
        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(10, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incorrect_one_choice_question(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $incorrectChoice->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_multiple_choice_question(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 15,
        ]);

        // Créer 2 bonnes réponses et 2 mauvaises
        $correctChoice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $correctChoice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        // L'étudiant sélectionne les 2 bonnes réponses
        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice1->id,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice2->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incomplete_multiple_choice(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 15,
        ]);

        $correctChoice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $correctChoice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        // L'étudiant ne sélectionne qu'une seule bonne réponse
        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice1->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_zero_for_multiple_choice_with_incorrect_selection(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 15,
        ]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        // L'étudiant sélectionne une bonne et une mauvaise
        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $incorrectChoice->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_boolean_question(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'boolean',
            'points' => 5,
        ]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
            'content' => 'Vrai',
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
            'content' => 'Faux',
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(5, $score);
    }

    #[Test]
    public function it_returns_zero_for_text_questions_without_manual_score(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 20,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Réponse de l\'étudiant',
            'score' => null, // Pas encore corrigé
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_returns_manual_score_for_corrected_text_questions(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 20,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Réponse de l\'étudiant',
            'score' => 15, // Corrigé par l'enseignant
        ]);

        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_total_assignment_score_correctly(): void
    {
        // Question 1: One choice (10 points)
        $question1 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        $correctChoice1 = Choice::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question1->id,
            'choice_id' => $correctChoice1->id,
        ]);

        // Question 2: Multiple choice (15 points)
        $question2 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 15,
        ]);

        $correctChoice2a = Choice::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => true,
        ]);

        $correctChoice2b = Choice::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => true,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'choice_id' => $correctChoice2a->id,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'choice_id' => $correctChoice2b->id,
        ]);

        // Question 3: Text (20 points, corrigée à 12)
        $question3 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 20,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'answer_text' => 'Réponse',
            'score' => 12,
        ]);

        $totalScore = $this->scoringService->calculateAssignmentScore($this->assignment);

        // 10 + 15 + 12 = 37
        $this->assertEquals(37, $totalScore);
    }

    #[Test]
    public function it_calculates_auto_correctable_score_only(): void
    {
        // Question 1: One choice (10 points) - auto-correctable
        $question1 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question1->id,
            'choice_id' => $correctChoice->id,
        ]);

        // Question 2: Text (20 points) - NOT auto-correctable
        $question2 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 20,
        ]);

        Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Réponse',
            'score' => 15,
        ]);

        $autoScore = $this->scoringService->calculateAutoCorrectableScore($this->assignment);

        // Seulement la question 1 (10 points)
        $this->assertEquals(10, $autoScore);
    }

    #[Test]
    public function it_returns_zero_for_unanswered_questions(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        // Pas de réponse créée
        $score = $this->scoringService->calculateQuestionScore($this->assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_verifies_answer_correctness_for_one_choice(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
        ]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertTrue($isCorrect);
    }

    #[Test]
    public function it_verifies_answer_incorrectness_for_one_choice(): void
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
            'points' => 10,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $incorrectChoice->id,
        ]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertFalse($isCorrect);
    }
}
