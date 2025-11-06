<?php

namespace Tests\Unit\Services\Student;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\Student\ExamSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ExamSessionServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ExamSessionService $sessionService;

    private Exam $exam;

    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->sessionService = app(ExamSessionService::class);

        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->exam = $this->createExamWithQuestions($teacher, [
            'duration' => 60,
            'start_time' => $now->copy()->subHour(),
            'end_time' => $now->copy()->addHours(3),
        ], questionCount: 0);

        $this->assignment = $this->createStartedAssignment($this->exam, $student, [
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
        $student = $this->createStudent();

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
        $student = $this->createStudent();
        $assignment = $this->createAssignmentForStudent($this->exam, $student);

        $this->sessionService->startExam($assignment);

        $assignment->refresh();
        $this->assertNotNull($assignment->started_at);
        $this->assertInstanceOf(Carbon::class, $assignment->started_at);
    }

    #[Test]
    public function does_not_override_started_at_if_already_started()
    {
        $originalStartTime = Carbon::now()->subMinutes(10);
        $student = $this->createStudent();
        $assignment = $this->createStartedAssignment($this->exam, $student, [
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
        $this->assertNull($this->assignment->score);
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
            true,
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

        $students = $this->createMultipleStudents(count($violationTypes));

        foreach ($violationTypes as $index => $type) {
            $assignment = $this->createStartedAssignment($this->exam, $students[$index], [
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
            true,
            true,
            $violationType
        );

        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertTrue($this->assignment->forced_submission);
        $this->assertEquals($violationType, $this->assignment->security_violation);
        $this->assertNull($this->assignment->score);
        $this->assertEquals(8.0, $this->assignment->auto_score);
    }

    // ============================================================================
    // SAVE MULTIPLE ANSWERS TESTS
    // ============================================================================

    #[Test]
    public function saves_single_choice_answer()
    {
        $question = $this->createQuestionForExam($this->exam, 'one_choice');
        $choice = $question->choices->first();

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
        $question = $this->createQuestionForExam($this->exam, 'multiple');
        $choices = $question->choices;

        $answers = [$question->id => [$choices[0]->id, $choices[2]->id]];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[0]->id,
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[2]->id,
        ]);

        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[1]->id,
        ]);
    }

    #[Test]
    public function saves_text_answer()
    {
        $question = $this->createQuestionForExam($this->exam, 'text');

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
        $question = $this->createQuestionForExam($this->exam, 'one_choice');
        $choices = $question->choices;

        $this->createAnswerForQuestion($this->assignment, $question, ['choice_id' => $choices[0]->id]);

        $answers = [$question->id => $choices[1]->id];
        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[0]->id,
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[1]->id,
        ]);
    }

    #[Test]
    public function saves_multiple_questions_answers_in_batch()
    {
        $question1 = $this->createQuestionForExam($this->exam, 'one_choice');
        $question2 = $this->createQuestionForExam($this->exam, 'text');
        $question3 = $this->createQuestionForExam($this->exam, 'multiple');

        $answers = [
            $question1->id => $question1->choices->first()->id,
            $question2->id => 'Réponse textuelle',
            $question3->id => [$question3->choices[0]->id, $question3->choices[1]->id],
        ];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question1->id,
            'choice_id' => $question1->choices->first()->id,
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Réponse textuelle',
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'choice_id' => $question3->choices[0]->id,
        ]);
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'choice_id' => $question3->choices[1]->id,
        ]);
    }

    #[Test]
    public function skips_invalid_question_ids()
    {
        $question = $this->createQuestionForExam($this->exam, 'one_choice');
        $choice = $question->choices->first();

        $invalidQuestionId = 99999;
        $answers = [
            $question->id => $choice->id,
            $invalidQuestionId => 123,
        ];

        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);

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

        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
        ]);
    }

    #[Test]
    public function clears_multiple_choice_answers_correctly()
    {
        $question = $this->createQuestionForExam($this->exam, 'multiple');
        $choices = $question->choices;

        $this->createAnswerForQuestion($this->assignment, $question, ['choice_id' => $choices[0]->id]);
        $this->createAnswerForQuestion($this->assignment, $question, ['choice_id' => $choices[1]->id]);

        $answers = [$question->id => [$choices[1]->id, $choices[2]->id]];
        $this->sessionService->saveMultipleAnswers($this->assignment, $this->exam, $answers);

        $this->assertDatabaseMissing('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choices[0]->id,
        ]);

        $answersCount = \App\Models\Answer::where('assignment_id', $this->assignment->id)
            ->where('question_id', $question->id)
            ->count();
        $this->assertEquals(2, $answersCount);
    }
}
