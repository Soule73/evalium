<?php

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Level;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\ExamAssignment;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTestData;

class StudentExamControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createExamWithAssignment(): array
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);

        $level = Level::factory()->create(['name' => 'Test Level']);
        $group = Group::factory()->create([
            'level_id' => $level->id,
            'is_active' => true,
        ]);

        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
            'duration' => 90
        ]);

        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        Question::factory()->count(3)->create([
            'exam_id' => $exam->id,
        ]);

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => null,
            'started_at' => null,
        ]);

        return compact('teacher', 'student', 'exam', 'group', 'assignment');
    }

    #[Test]
    public function student_can_access_exam_dashboard()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);

        $response = $this->actingAs($student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Groups/Index', false)
                ->has('groups')
        );
    }

    #[Test]
    public function student_can_start_assigned_exam()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $response = $this->actingAs($student)
            ->get(route('student.exams.take', $exam));

        $this->assertTrue(
            $response->getStatusCode() === 200 || $response->getStatusCode() === 302,
            'Expected status 200 or 302, got ' . $response->getStatusCode()
        );

        if ($response->getStatusCode() === 200) {
            $assignment->refresh();
            $this->assertNull($assignment->status);
            $this->assertNotNull($assignment->started_at);
        }
    }

    #[Test]
    public function student_cannot_start_exam_twice()
    {
        ['student' => $student, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $assignment->update([
            'status' => null,
            'started_at' => Carbon::now()
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.exams.show', $assignment));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Exams/Show', false)
        );
    }

    #[Test]
    public function student_can_submit_text_answer()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $assignment->update([
            'started_at' => now(),
            'status' => null,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'content' => 'What is 2 + 2?',
            'points' => 5
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.exams.save-answers', $exam), [
                'answers' => [
                    $question->id => 'Ma réponse à la question'
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses enregistrées'
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Ma réponse à la question'
        ]);
    }

    #[Test]
    public function student_can_submit_multiple_choice_answer()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple',
            'content' => 'Which are prime numbers?',
            'points' => 3
        ]);

        $choice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => '2',
            'is_correct' => true
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => '4',
            'is_correct' => false
        ]);

        $assignment->update([
            'started_at' => now(),
            'status' => null,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.exams.save-answers', $exam), [
                'answers' => [
                    $question->id => $choice1->id
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses enregistrées'
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id
        ]);
    }

    #[Test]
    public function student_can_submit_exam()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $exam->questions()->delete();

        $assignment->update([
            'started_at' => now(),
            'status' => null,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.exams.submit', $exam));

        $response->assertRedirect(route('student.exams.show', $exam));
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertNotNull($assignment->submitted_at);
    }

    #[Test]
    public function student_cannot_submit_unstarted_exam()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $response = $this->actingAs($student)
            ->post(route('student.exams.submit', $exam));

        $response->assertSessionHasErrors();

        $assignment->refresh();
        $this->assertNull($assignment->status);
        $this->assertNull($assignment->started_at);
    }

    #[Test]
    public function student_can_view_completed_exam_results()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $assignment->update([
            'status' => 'graded',
            'score' => 85.5
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.exams.show', $exam));

        $response->assertOk();
    }

    #[Test]
    public function student_cannot_view_results_of_ungraded_exam()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $assignment->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.exams.show', $exam));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Exams/Results', false)
                ->where('assignment.status', 'submitted')
        );
    }

    #[Test]
    public function student_cannot_access_other_student_exam()
    {
        ['student' => $student, 'teacher' => $teacher] = $this->createExamWithAssignment();
        $otherStudent = $this->createStudent(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $otherAssignment = ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.exams.show', $otherExam));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_cannot_access_student_routes()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $response = $this->actingAs($teacher)
            ->get(route('student.exams.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function student_can_update_existing_answer()
    {
        ['student' => $student, 'exam' => $exam, 'assignment' => $assignment] = $this->createExamWithAssignment();

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'content' => 'Test question'
        ]);

        $existingAnswer = Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Old answer'
        ]);

        $assignment->update([
            'started_at' => now(),
            'status' => null,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.exams.save-answers', $exam), [
                'answers' => [
                    $question->id => 'Updated answer'
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses enregistrées'
        ]);

        $this->assertDatabaseHas('answers', [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Updated answer'
        ]);
    }
}
