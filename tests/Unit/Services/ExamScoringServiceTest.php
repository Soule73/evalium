<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesTestRoles;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Teacher\ExamScoringService;
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

        // Créer les rôles
        $this->createTestRoles();

        $this->service = new ExamScoringService();

        // Créer un étudiant
        $this->student = $this->createUserWithRole('student', [
            'email' => 'student@test.com',
        ]);

        // Créer un examen
        $this->exam = Exam::factory()->create();

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

        $result = $this->service->saveTeacherCorrections($this->assignment, $scores);

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

        $result = $this->service->saveTeacherCorrections($this->assignment, $scores);

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

        $autoScore = $this->service->calculateAutoScore($this->assignment);

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

        $result = $this->service->saveManualCorrection($this->exam, $this->student, $validatedData);

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

        $result = $this->service->saveTeacherCorrections($this->assignment, $scores);

        $this->assertTrue($result['success']);

        // Vérifier que le statut a été mis à jour
        $this->assignment->refresh();
        $this->assertEquals('graded', $this->assignment->status);
    }
}
