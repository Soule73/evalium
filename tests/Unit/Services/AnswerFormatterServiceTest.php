<?php

namespace Tests\Unit\Services;

use App\Models\Choice;
use App\Services\Core\Answer\AnswerFormatterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AnswerFormatterServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private AnswerFormatterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->service = app(AnswerFormatterService::class);
    }

    #[Test]
    public function it_can_format_user_answers_for_frontend()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student, [
            'status' => 'submitted',
            'score' => 85.5,
        ]);

        $textQuestion = $this->createQuestionForExam($exam, 'text', [
            'content' => 'Text question',
            'points' => 5,
        ]);

        $multipleQuestion = $this->createQuestionForExam($exam, 'multiple', [
            'content' => 'Multiple choice question',
            'points' => 3,
        ]);

        $choice1 = Choice::factory()->create([
            'question_id' => $multipleQuestion->id,
            'content' => 'Choice 1',
            'is_correct' => true,
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $multipleQuestion->id,
            'content' => 'Choice 2',
            'is_correct' => false,
        ]);

        $this->createAnswerForQuestion($assignment, $textQuestion, [
            'answer_text' => 'Text answer',
            'score' => 4.5,
        ]);

        $this->createAnswerForQuestion($assignment, $multipleQuestion, [
            'choice_id' => $choice1->id,
            'score' => 3.0,
        ]);

        $assignment->load(['answers.choice', 'exam.questions.choices']);

        $result = $this->service->formatForFrontend($assignment);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertArrayHasKey($textQuestion->id, $result);
        $this->assertArrayHasKey($multipleQuestion->id, $result);

        $this->assertEquals('Text answer', $result[$textQuestion->id]['answer_text']);
        $this->assertEquals(4.5, $result[$textQuestion->id]['score']);

        $this->assertEquals($choice1->id, $result[$multipleQuestion->id]['choice_id']);
        $this->assertEquals(3.0, $result[$multipleQuestion->id]['score']);
    }

    #[Test]
    public function it_handles_answers_without_choices()
    {
        $student = $this->createStudent(['email' => 'student2@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question = $this->createQuestionForExam($exam, 'text');

        $this->createAnswerForQuestion($assignment, $question, [
            'answer_text' => 'Simple text answer',
            'choice_id' => null,
        ]);

        $assignment->load(['answers.choice', 'exam.questions.choices']);

        $result = $this->service->formatForFrontend($assignment);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($question->id, $result);
        $this->assertNull($result[$question->id]['choice_id']);
        $this->assertEquals('Simple text answer', $result[$question->id]['answer_text']);
    }

    #[Test]
    public function it_handles_empty_answers_collection()
    {
        $student = $this->createStudent(['email' => 'student3@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->formatForFrontend($assignment);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_can_format_single_answer()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question = $this->createQuestionForExam($exam, 'one_choice', ['points' => 5]);

        $choice = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => 'Correct answer',
            'is_correct' => true,
        ]);

        $answer = $this->createAnswerForQuestion($assignment, $question, [
            'choice_id' => $choice->id,
            'score' => 5.0,
            'feedback' => 'Good job!',
        ]);

        $answer->load('choice');
        $result = $this->service->formatSingleAnswer($answer);

        $this->assertEquals('single', $result['type']);
        $this->assertEquals($question->id, $result['question_id']);
        $this->assertEquals($choice->id, $result['choice_id']);
        $this->assertEquals(5.0, $result['score']);
        $this->assertEquals('Good job!', $result['feedback']);
    }

    #[Test]
    public function it_can_format_multiple_answers()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question = $this->createQuestionForExam($exam, 'multiple', ['points' => 10]);

        $choice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => 'Choice 1',
            'is_correct' => true,
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => 'Choice 2',
            'is_correct' => true,
        ]);

        $answer1 = $this->createAnswerForQuestion($assignment, $question, [
            'choice_id' => $choice1->id,
            'score' => 10.0,
            'feedback' => 'Correct!',
        ]);

        $answer2 = $this->createAnswerForQuestion($assignment, $question, [
            'choice_id' => $choice2->id,
            'score' => 10.0,
            'feedback' => 'Correct!',
        ]);

        $answer1->load('choice');
        $answer2->load('choice');

        $answers = collect([$answer1, $answer2]);
        $result = $this->service->formatMultipleAnswers($answers);

        $this->assertEquals('multiple', $result['type']);
        $this->assertEquals($question->id, $result['question_id']);
        $this->assertCount(2, $result['choices']);
        $this->assertEquals($choice1->id, $result['choices'][0]['choice_id']);
        $this->assertEquals($choice2->id, $result['choices'][1]['choice_id']);
        $this->assertEquals(10.0, $result['score']);
        $this->assertEquals('Correct!', $result['feedback']);
    }

    #[Test]
    public function it_can_check_if_assignment_has_answers()
    {
        $student = $this->createStudent(['email' => 'student_has_answers@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question = $this->createQuestionForExam($exam, 'text');

        $this->createAnswerForQuestion($assignment, $question, [
            'answer_text' => 'Test answer',
        ]);

        $this->assertTrue($this->service->hasAnswers($assignment));

        $emptyStudent = $this->createStudent(['email' => 'student_no_answers@test.com']);
        $emptyAssignment = $this->createAssignmentForStudent($exam, $emptyStudent);

        $this->assertFalse($this->service->hasAnswers($emptyAssignment));
    }

    #[Test]
    public function it_can_count_answered_questions()
    {
        $student = $this->createStudent(['email' => 'student_count@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question1 = $this->createQuestionForExam($exam, 'text');
        $question2 = $this->createQuestionForExam($exam, 'one_choice');
        $question3 = $this->createQuestionForExam($exam, 'multiple');

        $choice1 = Choice::factory()->create(['question_id' => $question2->id]);
        $choice2 = Choice::factory()->create(['question_id' => $question3->id]);
        $choice3 = Choice::factory()->create(['question_id' => $question3->id]);

        $this->createAnswerForQuestion($assignment, $question1, [
            'answer_text' => 'Answer 1',
        ]);

        $this->createAnswerForQuestion($assignment, $question2, [
            'choice_id' => $choice1->id,
        ]);

        $this->createAnswerForQuestion($assignment, $question3, [
            'choice_id' => $choice2->id,
        ]);

        $this->createAnswerForQuestion($assignment, $question3, [
            'choice_id' => $choice3->id,
        ]);

        $count = $this->service->countAnsweredQuestions($assignment);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_can_get_completion_stats()
    {
        $student = $this->createStudent(['email' => 'student_stats@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question1 = $this->createQuestionForExam($exam, 'text');
        $question2 = $this->createQuestionForExam($exam, 'one_choice');
        $question3 = $this->createQuestionForExam($exam, 'multiple');

        $choice = Choice::factory()->create(['question_id' => $question2->id]);

        $this->createAnswerForQuestion($assignment, $question1, [
            'answer_text' => 'Answer 1',
        ]);

        $this->createAnswerForQuestion($assignment, $question2, [
            'choice_id' => $choice->id,
        ]);

        $assignment->load('exam.questions');

        $stats = $this->service->getCompletionStats($assignment);

        $this->assertEquals(3, $stats['total_questions']);
        $this->assertEquals(2, $stats['answered_questions']);
        $this->assertEquals(1, $stats['unanswered_questions']);
        $this->assertEquals(66.67, $stats['completion_percentage']);
        $this->assertFalse($stats['is_complete']);
    }

    #[Test]
    public function it_can_get_completion_stats_for_complete_assignment()
    {
        $student = $this->createStudent(['email' => 'student_complete@test.com']);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question1 = $this->createQuestionForExam($exam, 'text');
        $question2 = $this->createQuestionForExam($exam, 'one_choice');

        $choice = Choice::factory()->create(['question_id' => $question2->id]);

        $this->createAnswerForQuestion($assignment, $question1, [
            'answer_text' => 'Answer 1',
        ]);

        $this->createAnswerForQuestion($assignment, $question2, [
            'choice_id' => $choice->id,
        ]);

        $assignment->load('exam.questions');

        $stats = $this->service->getCompletionStats($assignment);

        $this->assertEquals(2, $stats['total_questions']);
        $this->assertEquals(2, $stats['answered_questions']);
        $this->assertEquals(0, $stats['unanswered_questions']);
        $this->assertEquals(100, $stats['completion_percentage']);
        $this->assertTrue($stats['is_complete']);
    }

    #[Test]
    public function it_can_get_student_results_data()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $question = $this->createQuestionForExam($exam, 'text', ['points' => 10]);

        Choice::factory()->create(['question_id' => $question->id]);

        $this->createAnswerForQuestion($assignment, $question, [
            'answer_text' => 'Test answer',
            'score' => 8.5,
        ]);

        $result = $this->service->getStudentResultsData($assignment);

        $this->assertArrayHasKey('assignment', $result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('userAnswers', $result);
        $this->assertArrayHasKey('stats', $result);

        $this->assertEquals($assignment->id, $result['assignment']->id);
        $this->assertEquals($student->id, $result['student']->id);
        $this->assertIsArray($result['userAnswers']);
        $this->assertIsArray($result['stats']);
    }

    #[Test]
    public function it_can_get_student_results_data_in_group()
    {
        $student = $this->createStudent();
        $group = $this->createGroupWithStudents(studentCount: 0);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $group->students()->attach($student->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $question = $this->createQuestionForExam($exam, 'text', ['points' => 10]);

        $this->createAnswerForQuestion($assignment, $question, [
            'answer_text' => 'Test answer',
            'score' => 8.5,
        ]);

        $result = $this->service->getStudentResultsDataInGroup($exam, $group, $student);

        $this->assertArrayHasKey('assignment', $result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('group', $result);
        $this->assertArrayHasKey('creator', $result);
        $this->assertArrayHasKey('userAnswers', $result);
        $this->assertArrayHasKey('stats', $result);

        $this->assertEquals($assignment->id, $result['assignment']->id);
        $this->assertEquals($student->id, $result['student']->id);
        $this->assertEquals($group->id, $result['group']->id);
    }

    #[Test]
    public function it_throws_exception_when_student_not_in_group_for_results()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $otherGroup = $this->createGroupWithStudents(studentCount: 0);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Student does not belong to this group or is not active.');

        $this->service->getStudentResultsDataInGroup($exam, $otherGroup, $student);
    }

    #[Test]
    public function it_can_get_student_review_data()
    {
        $student = $this->createStudent();
        $group = $this->createGroupWithStudents(studentCount: 0);
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $assignment = $this->createAssignmentForStudent($exam, $student);

        $group->students()->attach($student->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $question = $this->createQuestionForExam($exam, 'text', ['points' => 10]);

        $this->createAnswerForQuestion($assignment, $question, [
            'answer_text' => 'Test answer',
            'score' => 8.5,
        ]);

        $result = $this->service->getStudentReviewData($exam, $group, $student);

        $this->assertArrayHasKey('assignment', $result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('group', $result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertArrayHasKey('userAnswers', $result);
        $this->assertArrayHasKey('totalQuestions', $result);
        $this->assertArrayHasKey('totalPoints', $result);

        $this->assertEquals(1, $result['totalQuestions']);
        $this->assertEquals(10, $result['totalPoints']);
    }

    #[Test]
    public function it_throws_exception_when_student_not_in_group_for_review()
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(questionCount: 0);
        $otherGroup = $this->createGroupWithStudents(studentCount: 0);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Student does not belong to this group.');

        $this->service->getStudentReviewData($exam, $otherGroup, $student);
    }

    #[Test]
    public function it_can_prepare_answer_data_for_choice_questions()
    {
        $result = $this->service->prepareAnswerData('one_choice', ['choice_id' => 5]);

        $this->assertEquals(5, $result['choice_id']);
        $this->assertNull($result['answer_text']);

        $result = $this->service->prepareAnswerData('multiple', ['choice_id' => 3]);

        $this->assertEquals(3, $result['choice_id']);
        $this->assertNull($result['answer_text']);

        $result = $this->service->prepareAnswerData('boolean', ['choice_id' => 1]);

        $this->assertEquals(1, $result['choice_id']);
        $this->assertNull($result['answer_text']);
    }

    #[Test]
    public function it_can_prepare_answer_data_for_text_questions()
    {
        $result = $this->service->prepareAnswerData('text', ['answer_text' => 'My answer']);

        $this->assertEquals('My answer', $result['answer_text']);
        $this->assertNull($result['choice_id']);

        $result = $this->service->prepareAnswerData('essay', ['answer_text' => 'Long essay']);

        $this->assertEquals('Long essay', $result['answer_text']);
        $this->assertNull($result['choice_id']);
    }

    #[Test]
    public function it_handles_missing_data_in_prepare_answer_data()
    {
        $result = $this->service->prepareAnswerData('one_choice', []);

        $this->assertNull($result['choice_id']);
        $this->assertNull($result['answer_text']);

        $result = $this->service->prepareAnswerData('text', []);

        $this->assertEquals('', $result['answer_text']);
        $this->assertNull($result['choice_id']);
    }
}
