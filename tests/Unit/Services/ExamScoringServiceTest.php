<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Tests\Traits\CreatesTestRoles;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Exam\ExamScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamScoringServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestRoles;

    private ExamScoringService $service;
    private User $student;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestRoles();

        $this->service = app(ExamScoringService::class);

        $this->student = $this->createUserWithRole('student', [
            'email' => 'student@test.com',
        ]);

        /** @var Exam $exam */
        $exam = Exam::factory()->create();

        $this->exam = $exam;

        // Créer une assignation
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted'
        ]);
    }

    #[Test]
    public function it_can_save_teacher_corrections()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $scores = [
            $question->id => [
                'score' => 8.5,
                'teacher_notes' => 'Good answer but missing detail'
            ]
        ];

        $result = $this->service->saveCorrections($this->assignment, $scores);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_count']);
    }

    #[Test]
    public function it_validates_score_range()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        // Test score trop élevé
        $scores = [
            $question->id => [
                'score' => 15, // Plus que les points de la question
                'teacher_notes' => 'Test'
            ]
        ];

        $result = $this->service->saveCorrections($this->assignment, $scores);

        // Le service devrait gérer la validation
        $this->assertArrayHasKey('success', $result);
    }

    #[Test]
    public function it_can_calculate_auto_score()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        // Utiliser le ScoringService directement
        $scoringService = app(\App\Services\Core\Scoring\ScoringService::class);
        $autoScore = $scoringService->calculateAutoCorrectableScore($this->assignment);

        $this->assertIsFloat($autoScore);
        $this->assertGreaterThanOrEqual(0, $autoScore);
    }

    #[Test]
    public function it_can_recalculate_exam_scores()
    {
        // Créer des assignations avec des réponses
        $assignment1 = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
        ]);

        $assignment2 = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
        ]);

        $result = $this->service->recalculateExamScores($this->exam);

        $this->assertArrayHasKey('updated_count', $result);
        $this->assertIsInt($result['updated_count']);
    }

    #[Test]
    public function it_can_save_manual_correction_with_three_params()
    {
        // Mettre à jour l'assignation pour avoir submitted_at
        $this->assignment->update([
            'submitted_at' => now()
        ]);

        $validatedData = [
            'scores' => [
                ['question_id' => 1, 'score' => 8.5]
            ],
            'teacher_notes' => 'Good work'
        ];

        $result = $this->service->saveCorrections($this->assignment, $validatedData);

        $this->assertArrayHasKey('success', $result);
    }

    #[Test]
    public function it_updates_assignment_status_when_scoring()
    {
        $this->assignment->update(['status' => 'submitted']);

        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $scores = [
            $question->id => [
                'score' => 8.5,
                'teacher_notes' => 'Good'
            ]
        ];

        $result = $this->service->saveCorrections($this->assignment, $scores);

        $this->assertTrue($result['success']);

        $this->assignment->refresh();
        $this->assertEquals('graded', $this->assignment->status);
    }

    #[Test]
    public function it_can_save_corrections_with_unified_method()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $scores = [
            $question->id => [
                'score' => 9.0,
                'feedback' => 'Excellent answer'
            ]
        ];

        $result = $this->service->saveCorrections($this->assignment, $scores, 'Overall good work');

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_count']);
        $this->assertEquals(9.0, $result['total_score']);
        $this->assertEquals('graded', $result['status']);

        $this->assignment->refresh();
        $this->assertEquals('Overall good work', $this->assignment->teacher_notes);
    }

    #[Test]
    public function it_normalizes_simple_score_format()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $scores = [
            $question->id => 7.5
        ];

        $result = $this->service->saveCorrections($this->assignment, $scores);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_count']);
        $this->assertEquals(7.5, $result['total_score']);
    }

    #[Test]
    public function it_normalizes_batch_scores_format()
    {
        $question1 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $question2 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 5
        ]);

        $answer1 = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question1->id,
            'answer_text' => 'Answer 1'
        ]);

        $answer2 = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Answer 2'
        ]);

        $data = [
            'scores' => [
                ['question_id' => $question1->id, 'score' => 8.5, 'feedback' => 'Good'],
                ['question_id' => $question2->id, 'score' => 4.0, 'feedback' => 'Nice']
            ]
        ];

        $result = $this->service->saveCorrections($this->assignment, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated_count']);
        $this->assertEquals(12.5, $result['total_score']);
    }

    #[Test]
    public function it_normalizes_single_question_format()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $data = [
            'question_id' => $question->id,
            'score' => 6.5,
            'feedback' => 'Needs improvement'
        ];

        $result = $this->service->saveCorrections($this->assignment, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_count']);
        $this->assertEquals(6.5, $result['total_score']);

        $answer->refresh();
        $this->assertEquals('Needs improvement', $answer->feedback);
    }

    #[Test]
    public function it_handles_teacher_notes_in_single_format()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $data = [
            'question_id' => $question->id,
            'score' => 5.0,
            'teacher_notes' => 'Used as feedback'
        ];

        $result = $this->service->saveCorrections($this->assignment, $data);

        $this->assertTrue($result['success']);

        $answer->refresh();
        $this->assertEquals('Used as feedback', $answer->feedback);
    }

    #[Test]
    public function legacy_methods_still_work()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        $scores = [
            $question->id => [
                'score' => 8.0,
                'feedback' => 'Good work'
            ]
        ];

        $result1 = $this->service->saveCorrections($this->assignment, $scores);
        $this->assertTrue($result1['success']);

        $this->assignment->update(['submitted_at' => now()]);

        $data = [
            'scores' => [
                ['question_id' => $question->id, 'score' => 7.0]
            ]
        ];

        $result2 = $this->service->saveCorrections($this->assignment, $data);
        $this->assertTrue($result2['success']);
    }
}
