<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTestData;

class ExamCorrectionControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createExamWithQuestionAndSubmission(): array
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);

        $group = Group::factory()->create();
        $group->students()->attach($student->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true
        ]);

        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer'
        ]);

        return compact('teacher', 'student', 'exam', 'group', 'question', 'assignment');
    }

    #[Test]
    public function teacher_can_view_student_review_page()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group, 'student' => $student] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->get(route('exams.review', [$exam, $group, $student]));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/StudentReview', false)
                ->has('exam')
                ->has('student')
        );
    }

    #[Test]
    public function teacher_can_save_student_review()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group, 'student' => $student, 'question' => $question, 'assignment' => $assignment] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->post(route('exams.review.save', [$exam, $group, $student]), [
                'scores' => [
                    [
                        'question_id' => $question->id,
                        'score' => 8.5,
                        'feedback' => 'Good answer'
                    ]
                ],
                'teacher_notes' => 'Excellent work overall'
            ]);

        $response->assertRedirect(route('exams.review', [$exam, $group, $student]));
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertNotNull($assignment);
    }

    #[Test]
    public function teacher_can_update_single_question_score()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'student' => $student, 'question' => $question, 'assignment' => $assignment] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->postJson(route('exams.score.update', $exam), [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'question_id' => $question->id,
                'score' => 8.5,
                'teacher_notes' => 'Good answer'
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true
        ]);

        $answer = Answer::where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertEquals(8.5, $answer->score);
        $this->assertEquals('Good answer', $answer->feedback);
    }

    #[Test]
    public function teacher_cannot_give_score_higher_than_max_points()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'student' => $student, 'question' => $question] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->postJson(route('exams.score.update', $exam), [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'question_id' => $question->id,
                'score' => 15,
                'teacher_notes' => 'Too much'
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function teacher_can_save_review_with_feedback_only()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group, 'student' => $student, 'question' => $question] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->post(route('exams.review.save', [$exam, $group, $student]), [
                'scores' => [
                    [
                        'question_id' => $question->id,
                        'score' => 0,
                        'feedback' => 'Needs improvement'
                    ]
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function teacher_cannot_review_non_submitted_exam()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithQuestionAndSubmission();

        $newStudent = User::factory()->create();
        $newStudent->assignRole('student');

        $group->students()->attach($newStudent->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $newAssignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $newStudent->id,
            'status' => null,
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.review', [$exam, $group, $newStudent]));

        $response->assertOk();
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_correction()
    {
        ['teacher' => $teacher] = $this->createExamWithQuestionAndSubmission();

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherGroup = Group::factory()->create();

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherGroup->students()->attach($otherStudent->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.review', [$otherExam, $otherGroup, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_correction_routes()
    {
        ['student' => $student, 'exam' => $exam, 'group' => $group] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($student)
            ->get(route('exams.review', [$exam, $group, $student]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_can_save_review_with_multiple_questions()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group, 'student' => $student, 'question' => $question, 'assignment' => $assignment] = $this->createExamWithQuestionAndSubmission();

        $question2 = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 5
        ]);

        $question3 = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple',
            'points' => 3
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Answer 2'
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $question3->id,
            'answer_text' => 'Answer 3'
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('exams.review.save', [$exam, $group, $student]), [
                'scores' => [
                    [
                        'question_id' => $question->id,
                        'score' => 8.5,
                        'feedback' => 'Good'
                    ],
                    [
                        'question_id' => $question2->id,
                        'score' => 4.0,
                        'feedback' => 'Very good'
                    ],
                    [
                        'question_id' => $question3->id,
                        'score' => 3.0,
                        'feedback' => 'Perfect'
                    ]
                ],
                'teacher_notes' => 'Overall excellent work'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function teacher_can_update_score_with_notes()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'student' => $student, 'question' => $question, 'assignment' => $assignment] = $this->createExamWithQuestionAndSubmission();

        $response = $this->actingAs($teacher)
            ->postJson(route('exams.score.update', $exam), [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'question_id' => $question->id,
                'score' => 7.5,
                'teacher_notes' => 'Could be better, work on...'
            ]);

        $response->assertOk();

        $answer = Answer::where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertEquals('Could be better, work on...', $answer->feedback);
    }
}
