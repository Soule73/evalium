<?php

namespace Tests\Unit\Services\Core\Scoring;

use App\Models\Answer;
use App\Models\Choice;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ScoringServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->scoringService = app(ScoringService::class);
    }

    #[Test]
    public function it_calculates_score_for_correct_one_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $correctChoice->id]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(10, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incorrect_one_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_multiple_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'multiple', ['points' => 15]);

        $correctChoices = $question->choices()->where('is_correct', true)->get();
        $correctChoice1 = $correctChoices[0];
        $correctChoice2 = $correctChoices[1];

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice1->id,
        ]);

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice2->id,
        ]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incomplete_multiple_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'multiple', ['points' => 15]);

        $correctChoice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $correctChoice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $correctChoice1->id]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_zero_for_multiple_choice_with_incorrect_selection(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'multiple', ['points' => 15]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $correctChoice->id]);
        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_boolean_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'boolean', ['points' => 5]);

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

        $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $correctChoice->id]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(5, $score);
    }

    #[Test]
    public function it_returns_zero_for_text_questions_without_manual_score(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'text', ['points' => 20]);

        $this->createAnswerForQuestion($assignment, $question, ['answer_text' => 'Réponse de l\'étudiant', 'score' => null]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_returns_manual_score_for_corrected_text_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'text', ['points' => 20]);

        $this->createAnswerForQuestion($assignment, $question, ['answer_text' => 'Réponse de l\'étudiant', 'score' => 15]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_total_assignment_score_correctly(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Total Score', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $multipleChoiceQuestion = $this->createQuestionForExam($exam, 'multiple', ['points' => 15]);
        $multipleCorrectChoices = $multipleChoiceQuestion->choices()->where('is_correct', true)->get();

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $multipleChoiceQuestion->id,
            'choice_id' => $multipleCorrectChoices[0]->id,
        ]);

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $multipleChoiceQuestion->id,
            'choice_id' => $multipleCorrectChoices[1]->id,
        ]);

        $oneChoiceQuestion = $this->createQuestionForExam($exam, 'one_choice', ['points' => 12]);
        $oneChoiceCorrect = $oneChoiceQuestion->choices()->where('is_correct', true)->first();

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $oneChoiceQuestion->id,
            'choice_id' => $oneChoiceCorrect->id,
        ]);

        $textQuestion = $this->createQuestionForExam($exam, 'text', ['points' => 10]);

        Answer::factory()->create([
            'assignment_id' => $assignment->id,
            'question_id' => $textQuestion->id,
            'answer_text' => 'Sample answer',
            'score' => 10,
        ]);

        $totalScore = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(37, $totalScore);
    }

    #[Test]
    public function it_calculates_auto_correctable_score_only(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question1 = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        $this->createAnswerForQuestion($assignment, $question1, ['choice_id' => $correctChoice->id]);

        $question2 = $this->createQuestionForExam($exam, 'text', ['points' => 20]);

        $this->createAnswerForQuestion($assignment, $question2, ['answer_text' => 'Réponse', 'score' => 15]);

        $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

        $this->assertEquals(10, $autoScore);
    }

    #[Test]
    public function it_returns_zero_for_unanswered_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $score = $this->scoringService->calculateQuestionScore($assignment, $question);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_verifies_answer_correctness_for_one_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $answer = $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $correctChoice->id]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertTrue($isCorrect);
    }

    #[Test]
    public function it_verifies_answer_incorrectness_for_one_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0, examAttributes: ['title' => 'Test Exam for Scoring', 'duration' => 60]);
        $assignment = $this->createAssignmentForStudent($exam, $student, ['status' => 'submitted', 'submitted_at' => now()]);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $answer = $this->createAnswerForQuestion($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertFalse($isCorrect);
    }
}
