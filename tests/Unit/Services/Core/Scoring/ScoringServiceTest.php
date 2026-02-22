<?php

namespace Tests\Unit\Services\Core\Scoring;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\AssessmentAssignment;
use App\Models\Choice;
use App\Models\Question;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function createQuestion($assessment, string $type, array $attributes = [])
    {
        $question = Question::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'type' => $type,
        ], $attributes));

        if (in_array($type, ['one_choice', 'boolean'])) {
            Choice::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
            Choice::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        } elseif ($type === 'multiple') {
            Choice::factory()->count(2)->create(['question_id' => $question->id, 'is_correct' => true]);
            Choice::factory()->count(2)->create(['question_id' => $question->id, 'is_correct' => false]);
        }

        return $question;
    }

    private function createAnswer($assignment, $question, array $attributes = [])
    {
        return Answer::factory()->create(array_merge([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ], $attributes));
    }

    #[Test]
    public function it_calculates_score_for_correct_one_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(10, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incorrect_one_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswer($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_multiple_choice_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'multiple', ['points' => 15]);

        $correctChoices = $question->choices()->where('is_correct', true)->get();
        $correctChoice1 = $correctChoices[0];
        $correctChoice2 = $correctChoices[1];

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice1->id,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice2->id,
        ]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_zero_for_incomplete_multiple_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'multiple', ['points' => 15]);

        $correctChoice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $correctChoice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice1->id]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_zero_for_multiple_choice_with_incorrect_selection(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'multiple', ['points' => 15]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);
        $this->createAnswer($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_score_for_correct_boolean_question(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'boolean', ['points' => 5]);

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

        $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(5, $score);
    }

    #[Test]
    public function it_returns_zero_for_text_questions_without_manual_score(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'text', ['points' => 20]);

        $this->createAnswer($assignment, $question, ['answer_text' => 'Réponse de l\'étudiant', 'score' => null]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_returns_manual_score_for_corrected_text_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'text', ['points' => 20]);

        $this->createAnswer($assignment, $question, ['answer_text' => 'Réponse de l\'étudiant', 'score' => 15]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(15, $score);
    }

    #[Test]
    public function it_calculates_total_assignment_score_correctly(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Total Score', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $multipleChoiceQuestion = $this->createQuestion($assessment, 'multiple', ['points' => 15]);
        $multipleCorrectChoices = $multipleChoiceQuestion->choices()->where('is_correct', true)->get();

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $multipleChoiceQuestion->id,
            'choice_id' => $multipleCorrectChoices[0]->id,
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $multipleChoiceQuestion->id,
            'choice_id' => $multipleCorrectChoices[1]->id,
        ]);

        $oneChoiceQuestion = $this->createQuestion($assessment, 'one_choice', ['points' => 12]);
        $oneChoiceCorrect = $oneChoiceQuestion->choices()->where('is_correct', true)->first();

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $oneChoiceQuestion->id,
            'choice_id' => $oneChoiceCorrect->id,
        ]);

        $textQuestion = $this->createQuestion($assessment, 'text', ['points' => 10]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
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
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $question1 = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        $this->createAnswer($assignment, $question1, ['choice_id' => $correctChoice->id]);

        $question2 = $this->createQuestion($assessment, 'text', ['points' => 20]);

        $this->createAnswer($assignment, $question2, ['answer_text' => 'Réponse', 'score' => 15]);

        $autoScore = $this->scoringService->calculateAutoCorrectableScore($assignment);

        $this->assertEquals(10, $autoScore);
    }

    #[Test]
    public function it_returns_zero_for_unanswered_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $assignment->loadMissing(['assessment.questions.choices', 'answers']);
        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_verifies_answer_correctness_for_one_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        $correctChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $answer = $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertTrue($isCorrect);
    }

    #[Test]
    public function it_verifies_answer_incorrectness_for_one_choice(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment for Scoring', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        /** @var Question $question */
        $question = $this->createQuestion($assessment, 'one_choice', ['points' => 10]);

        Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $incorrectChoice = Choice::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $answer = $this->createAnswer($assignment, $question, ['choice_id' => $incorrectChoice->id]);

        $isCorrect = $this->scoringService->isAnswerCorrect($question, collect([$answer]));

        $this->assertFalse($isCorrect);
    }

    #[Test]
    public function it_saves_manual_grades_for_single_answer_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $question = $this->createQuestion($assessment, 'text', ['points' => 10]);
        $answer = $this->createAnswer($assignment, $question, ['answer_text' => 'Student answer']);

        $scores = [
            ['question_id' => $question->id, 'score' => 8.5, 'feedback' => 'Good answer'],
        ];

        $result = $this->scoringService->saveManualGrades($assignment, $scores, 'Well done overall');

        $this->assertEquals(1, $result['updated_count']);
        $this->assertEquals(8.5, $result['total_score']);
        $this->assertEquals('graded', $result['status']);

        // Reload assignment from database
        $assignment = AssessmentAssignment::find($assignment->id);
        $this->assertEquals(8.5, $assignment->score);
        $this->assertEquals('Well done overall', $assignment->teacher_notes);

        $answer->refresh();
        $this->assertEquals(8.5, $answer->score);
        $this->assertEquals('Good answer', $answer->feedback);
    }

    #[Test]
    public function it_saves_manual_grades_for_multiple_choice_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $question = $this->createQuestion($assessment, 'multiple', ['points' => 10]);

        $correctChoice1 = $question->choices()->where('is_correct', true)->first();
        $correctChoice2 = $question->choices()->where('is_correct', true)->skip(1)->first();

        // Create multiple answers for multiple choice
        $answer1 = $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice1->id]);
        $answer2 = $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice2->id]);

        $scores = [
            ['question_id' => $question->id, 'score' => 7.0, 'feedback' => 'Partial credit'],
        ];

        $result = $this->scoringService->saveManualGrades($assignment, $scores);

        $this->assertEquals(1, $result['updated_count']);

        $answer1->refresh();
        $answer2->refresh();

        // First answer should have the score
        $this->assertEquals(7.0, $answer1->score);
        $this->assertEquals('Partial credit', $answer1->feedback);

        // Second answer should have 0 score but same feedback
        $this->assertEquals(0, $answer2->score);
        $this->assertEquals('Partial credit', $answer2->feedback);
    }

    #[Test]
    public function it_saves_manual_grades_for_multiple_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $question1 = $this->createQuestion($assessment, 'text', ['points' => 10]);
        $question2 = $this->createQuestion($assessment, 'text', ['points' => 15]);

        $this->createAnswer($assignment, $question1, ['answer_text' => 'Answer 1']);
        $this->createAnswer($assignment, $question2, ['answer_text' => 'Answer 2']);

        $scores = [
            ['question_id' => $question1->id, 'score' => 8.0, 'feedback' => 'Good'],
            ['question_id' => $question2->id, 'score' => 12.5, 'feedback' => 'Very good'],
        ];

        $result = $this->scoringService->saveManualGrades($assignment, $scores, 'Excellent work');

        $this->assertEquals(2, $result['updated_count']);
        $this->assertEquals(20.5, $result['total_score']);

        // Reload assignment from database
        $assignment = AssessmentAssignment::find($assignment->id);
        $this->assertEquals(20.5, $assignment->score);
    }

    #[Test]
    public function it_handles_empty_scores_array_gracefully(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0, assessmentAttributes: ['title' => 'Test Assessment', 'duration_minutes' => 60]);
        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        $result = $this->scoringService->saveManualGrades($assignment, [], 'No answers graded');

        $this->assertEquals(0, $result['updated_count']);
        $this->assertEquals(0.0, $result['total_score']);
    }

    #[Test]
    public function calculate_assignment_score_does_not_cause_n_plus_one(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(
            questionCount: 20,
            assessmentAttributes: ['title' => 'Performance Test', 'duration_minutes' => 60]
        );

        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        foreach ($assessment->questions as $question) {
            $correctChoice = $question->choices()->where('is_correct', true)->first();
            if ($correctChoice) {
                $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $queryCount = count(DB::getQueryLog());

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(25, $queryCount, "Expected <= 25 queries for scoring 20 questions (would be 60+ with N+1), but got {$queryCount}");
    }

    #[Test]
    public function scoring_performance_with_50_questions(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(
            questionCount: 50,
            assessmentAttributes: ['title' => 'Performance Test 50', 'duration_minutes' => 120]
        );

        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        foreach ($assessment->questions as $question) {
            $correctChoice = $question->choices()->where('is_correct', true)->first();
            if ($correctChoice) {
                $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);
            }
        }

        $assignment->refresh();
        $start = microtime(true);

        $score = $this->scoringService->calculateAssignmentScore($assignment);

        $duration = (microtime(true) - $start) * 1000;

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(150, $duration, "Scoring 50 questions took {$duration}ms (expected < 150ms)");
    }

    #[Test]
    public function calculate_auto_correctable_score_does_not_cause_n_plus_one(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(
            questionCount: 15,
            assessmentAttributes: ['title' => 'Auto Score Test', 'duration_minutes' => 60]
        );

        $questions = $assessment->questions;
        $questions->take(5)->each(fn ($q) => $q->update(['type' => 'text', 'points' => 10]));
        $questions->skip(5)->each(fn ($q) => $q->update(['type' => 'one_choice', 'points' => 10]));

        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        foreach ($assessment->fresh()->questions as $question) {
            if ($question->type === QuestionType::Text) {
                $this->createAnswer($assignment, $question, ['answer_text' => 'Student text answer']);
            } else {
                $correctChoice = $question->choices()->where('is_correct', true)->first();
                if ($correctChoice) {
                    $this->createAnswer($assignment, $question, ['choice_id' => $correctChoice->id]);
                }
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $score = $this->scoringService->calculateAutoCorrectableScore($assignment);

        $queryCount = count(DB::getQueryLog());

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(10, $queryCount, "Expected <= 10 queries for auto-scoring (would be 30+ with N+1), but got {$queryCount}");
    }

    #[Test]
    public function save_manual_grades_does_not_cause_n_plus_one(): void
    {
        $student = $this->createStudent(['email' => 'student@test.com']);
        $assessment = $this->createAssessmentWithQuestions(
            questionCount: 10,
            assessmentAttributes: ['title' => 'Manual Grade Test', 'duration_minutes' => 60]
        );

        $assessment->questions->each(fn ($q) => $q->update(['type' => 'text', 'points' => 10]));
        $questions = $assessment->fresh()->questions;

        $assignment = $this->createAssignmentForStudent($assessment, $student, ['submitted_at' => now()]);

        foreach ($questions as $question) {
            $this->createAnswer($assignment, $question, ['answer_text' => 'Student answer']);
        }

        $scores = [];
        foreach ($questions as $question) {
            $scores[] = [
                'question_id' => $question->id,
                'score' => 8.5,
                'feedback' => 'Good answer',
            ];
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $this->scoringService->saveManualGrades($assignment, $scores, 'Well done');

        $queryCount = count(DB::getQueryLog());

        $this->assertEquals(10, $result['updated_count']);
        $this->assertLessThanOrEqual(40, $queryCount, "Expected reasonable query count for manual grading 10 questions, but got {$queryCount}");
    }
}
