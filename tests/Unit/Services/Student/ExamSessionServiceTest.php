<?php

namespace Tests\Unit\Services\Student;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Student\ExamSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamSessionService $sessionService;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->sessionService = app(ExamSessionService::class);

        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'duration' => 60,
            'start_time' => $now->copy()->subHour(),
            'end_time' => $now->copy()->addHours(3),
        ]);

        $this->exam = $exam;

        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => $now->copy()->subMinutes(30),
            'status' => null,
        ]);

        $this->assignment->load('exam');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ============================================================================
    // FIND OR CREATE ASSIGNMENT TESTS
    // ============================================================================

    #[Test]
    public function finds_existing_assignment()
    {
        $student = $this->assignment->student;

        $result = $this->sessionService->findOrCreateAssignment($this->exam, $student);

        $this->assertEquals($this->assignment->id, $result->id);
    }

    #[Test]
    public function creates_new_assignment_if_not_exists()
    {
        $student = \App\Models\User::factory()->create();

        $result = $this->sessionService->findOrCreateAssignment($this->exam, $student);

        $this->assertInstanceOf(ExamAssignment::class, $result);
        $this->assertEquals($this->exam->id, $result->exam_id);
        $this->assertEquals($student->id, $result->student_id);
        $this->assertDatabaseHas('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $student->id,
        ]);
    }

    // ============================================================================
    // START EXAM TESTS
    // ============================================================================

    #[Test]
    public function starts_exam_successfully()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => null,
        ]);

        $this->sessionService->startExam($assignment);

        $assignment->refresh();
        $this->assertNotNull($assignment->started_at);
        $this->assertInstanceOf(Carbon::class, $assignment->started_at);
    }

    #[Test]
    public function does_not_override_started_at_if_already_started()
    {
        $originalStartTime = Carbon::now()->subMinutes(10);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => $originalStartTime,
        ]);

        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $this->sessionService->startExam($assignment);

        $assignment->refresh();
        $this->assertEquals($originalStartTime->format('Y-m-d H:i:s'), $assignment->started_at->format('Y-m-d H:i:s'));
    }

    // ============================================================================
    // SUBMIT EXAM TESTS
    // ============================================================================

    #[Test]
    public function submits_exam_with_auto_score_when_no_text_questions()
    {
        $autoScore = 15.5;

        $this->sessionService->submitExam($this->assignment, $autoScore, false, false);

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertNotNull($this->assignment->submitted_at);
        $this->assertEquals($autoScore, $this->assignment->score);
        $this->assertEquals($autoScore, $this->assignment->auto_score);
        $this->assertFalse($this->assignment->forced_submission);
    }

    #[Test]
    public function submits_exam_without_final_score_when_has_text_questions()
    {
        $autoScore = 10.0;

        $this->sessionService->submitExam($this->assignment, $autoScore, true, false);

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertNotNull($this->assignment->submitted_at);
        $this->assertNull($this->assignment->score); // Pas de score final car questions texte
        $this->assertEquals($autoScore, $this->assignment->auto_score);
        $this->assertFalse($this->assignment->forced_submission);
    }

    #[Test]
    public function submits_exam_as_forced_submission_on_security_violation()
    {
        $autoScore = 12.0;

        $this->sessionService->submitExam($this->assignment, $autoScore, false, true);

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertNotNull($this->assignment->submitted_at);
        $this->assertNull($this->assignment->score);
        $this->assertEquals($autoScore, $this->assignment->auto_score);
        $this->assertTrue($this->assignment->forced_submission);
    }

    #[Test]
    public function submits_exam_with_null_auto_score_preserves_existing()
    {
        $existingAutoScore = 8.5;
        $this->assignment->update(['auto_score' => $existingAutoScore]);

        $this->sessionService->submitExam($this->assignment, null, false, false);

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertEquals($existingAutoScore, $this->assignment->auto_score);
    }

    #[Test]
    public function submits_exam_with_both_text_questions_and_security_violation()
    {
        $autoScore = 7.0;

        $this->sessionService->submitExam($this->assignment, $autoScore, true, true);

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertNull($this->assignment->score);
        $this->assertEquals($autoScore, $this->assignment->auto_score);
        $this->assertTrue($this->assignment->forced_submission);
    }

    #[Test]
    public function records_violation_type_when_security_violation_occurs()
    {
        $autoScore = 10.0;
        $violationType = 'tab_switch';

        $this->sessionService->submitExam(
            $this->assignment,
            $autoScore,
            false,
            true, // isSecurityViolation
            $violationType
        );

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertTrue($this->assignment->forced_submission);
        $this->assertEquals($violationType, $this->assignment->security_violation);
        $this->assertEquals($autoScore, $this->assignment->auto_score);
        $this->assertNull($this->assignment->score);
    }

    #[Test]
    public function does_not_record_violation_type_when_no_security_violation()
    {
        $autoScore = 15.0;

        $this->sessionService->submitExam(
            $this->assignment,
            $autoScore,
            false,
            false,
            null
        );

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertFalse($this->assignment->forced_submission);
        $this->assertNull($this->assignment->security_violation);
        $this->assertEquals($autoScore, $this->assignment->score);
    }

    #[Test]
    public function records_different_violation_types()
    {
        $violationTypes = [
            'tab_switch',
            'fullscreen_exit',
            'browser_change',
            'copy_paste',
            'suspicious_activity',
        ];

        foreach ($violationTypes as $type) {
            $assignment = ExamAssignment::factory()->create([
                'exam_id' => $this->exam->id,
                'started_at' => Carbon::now()->subMinutes(10),
            ]);

            $this->sessionService->submitExam(
                $assignment,
                10.0,
                false,
                true,
                $type
            );

            $assignment->refresh();
            $this->assertEquals($type, $assignment->security_violation);
            $this->assertTrue($assignment->forced_submission);
        }
    }

    #[Test]
    public function does_not_record_violation_type_if_not_provided()
    {
        $this->sessionService->submitExam(
            $this->assignment,
            12.0,
            false,
            true,
            null
        );

        $this->assignment->refresh();
        $this->assertTrue($this->assignment->forced_submission);
        $this->assertNull($this->assignment->security_violation);
    }

    #[Test]
    public function security_violation_with_text_questions_records_both()
    {
        $violationType = 'tab_switch';

        $this->sessionService->submitExam(
            $this->assignment,
            8.0,
            true, // questions texte
            true, // violation
            $violationType
        );

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertTrue($this->assignment->forced_submission);
        $this->assertEquals($violationType, $this->assignment->security_violation);
        $this->assertNull($this->assignment->score); // Pas de score final (texte + violation)
        $this->assertEquals(8.0, $this->assignment->auto_score);
    }

    // ============================================================================
    // SAVE MULTIPLE ANSWERS TESTS
    // ============================================================================

    #[Test]
    public function saves_single_choice_answer()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
        ]);

        $choice = \App\Models\Choice::factory()->create([
            'question_id' => $question->id,
        ]);

        $answers = [$question->id => $choice->id];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
            'answer_text' => null,
        ]);
    }

    #[Test]
    public function saves_multiple_choice_answers()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
        ]);

        $choice1 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);
        $choice2 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);
        $choice3 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);

        $answers = [$question->id => [$choice1->id, $choice3->id]];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id,
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice3->id,
        ]);

        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice2->id,
        ]);
    }

    #[Test]
    public function saves_text_answer()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
        ]);

        $answerText = 'Ceci est ma réponse textuelle détaillée.';
        $answers = [$question->id => $answerText];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => null,
            'answer_text' => $answerText,
        ]);
    }

    #[Test]
    public function clears_previous_answers_before_saving_new_ones()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
        ]);

        $oldChoice = \App\Models\Choice::factory()->create(['question_id' => $question->id]);
        $newChoice = \App\Models\Choice::factory()->create(['question_id' => $question->id]);

        // Créer une ancienne réponse
        \App\Models\Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $oldChoice->id,
        ]);

        // Sauvegarder nouvelle réponse
        $answers = [$question->id => $newChoice->id];
        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        // L'ancienne réponse doit être supprimée
        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $oldChoice->id,
        ]);

        // La nouvelle réponse doit exister
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $newChoice->id,
        ]);
    }

    #[Test]
    public function saves_multiple_questions_answers_in_batch()
    {
        $question1 = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
        ]);
        $choice1 = \App\Models\Choice::factory()->create(['question_id' => $question1->id]);

        $question2 = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
        ]);

        $question3 = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
        ]);
        $choice3a = \App\Models\Choice::factory()->create(['question_id' => $question3->id]);
        $choice3b = \App\Models\Choice::factory()->create(['question_id' => $question3->id]);

        $answers = [
            $question1->id => $choice1->id,
            $question2->id => 'Réponse textuelle',
            $question3->id => [$choice3a->id, $choice3b->id],
        ];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        // Vérifier question 1
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question1->id,
            'choice_id' => $choice1->id,
        ]);

        // Vérifier question 2
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Réponse textuelle',
        ]);

        // Vérifier question 3
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'choice_id' => $choice3a->id,
        ]);
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'choice_id' => $choice3b->id,
        ]);
    }

    #[Test]
    public function skips_invalid_question_ids()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'one_choice',
        ]);
        $choice = \App\Models\Choice::factory()->create(['question_id' => $question->id]);

        $invalidQuestionId = 99999;
        $answers = [
            $question->id => $choice->id,
            $invalidQuestionId => 123, // Question inexistante
        ];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        // Vérifier que la réponse valide est sauvegardée
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);

        // Vérifier qu'aucune réponse n'est créée pour l'ID invalide
        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $invalidQuestionId,
        ]);
    }

    #[Test]
    public function handles_empty_answers_array()
    {
        $answers = [];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        // Vérifier qu'aucune réponse n'est créée
        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
        ]);
    }

    #[Test]
    public function clears_multiple_choice_answers_correctly()
    {
        $question = \App\Models\Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
        ]);

        $choice1 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);
        $choice2 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);
        $choice3 = \App\Models\Choice::factory()->create(['question_id' => $question->id]);

        // Créer anciennes réponses multiples
        \App\Models\Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id,
        ]);
        \App\Models\Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice2->id,
        ]);

        // Sauvegarder nouvelles réponses différentes
        $answers = [$question->id => [$choice2->id, $choice3->id]];
        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        // Vérifier que choice1 a été supprimée
        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id,
        ]);

        // Vérifier que choice2 et choice3 existent (nouvelles)
        $answersCount = \App\Models\Answer::where('assignment_id', $this->assignment->id)
            ->where('question_id', $question->id)
            ->count();
        $this->assertEquals(2, $answersCount);
    }
}
