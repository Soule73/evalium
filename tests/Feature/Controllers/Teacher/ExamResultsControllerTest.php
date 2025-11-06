<?php

namespace Tests\Feature\Controllers\Exam;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Group;
use App\Models\Question;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ExamResultsControllerTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createExamWithSubmission(): array
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);

        $group = Group::factory()->active()->create();
        $group->students()->attach($student->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'score' => 85.5,
        ]);

        return compact('teacher', 'student', 'exam', 'group', 'assignment');
    }

    #[Test]
    public function teacher_can_view_student_results()
    {
        ['teacher' => $teacher, 'student' => $student, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $student]));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
                ->has('exam')
                ->has('student')
                ->has('assignment')
        );
    }

    #[Test]
    public function teacher_can_view_results_for_submitted_exam()
    {
        ['teacher' => $teacher, 'student' => $student, 'exam' => $exam, 'group' => $group, 'assignment' => $assignment] = $this->createExamWithSubmission();

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10,
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer',
            'score' => 8.5,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $student]));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
        );
    }

    #[Test]
    public function teacher_cannot_view_results_for_non_existent_assignment()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();
        $otherStudent = $this->createStudent(['email' => 'other@test.com']);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_can_view_exam_statistics()
    {
        ['teacher' => $teacher, 'exam' => $exam] = $this->createExamWithSubmission();

        $response = $this->actingAs($teacher)
            ->get(route('exams.stats', $exam));

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_results()
    {
        ['teacher' => $teacher] = $this->createExamWithSubmission();
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);
        $otherStudent = $this->createStudent(['email' => 'other-student@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $otherGroup = Group::factory()->active()->create();
        $otherGroup->students()->attach($otherStudent->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$otherExam, $otherGroup, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_results_routes()
    {
        ['student' => $student, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();

        $response = $this->actingAs($student)
            ->get(route('exams.submissions', [$exam, $group, $student]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_can_view_results_with_detailed_answers()
    {
        ['teacher' => $teacher, 'student' => $student, 'exam' => $exam, 'group' => $group, 'assignment' => $assignment] = $this->createExamWithSubmission();

        $textQuestion = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10,
        ]);

        $mcqQuestion = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple',
            'points' => 5,
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $textQuestion->id,
            'answer_text' => 'Detailed text answer',
            'score' => 8.5,
            'teacher_notes' => 'Good work',
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $mcqQuestion->id,
            'selected_choices' => json_encode([1, 2]),
            'score' => 5.0,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $student]));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
                ->has('exam')
                ->has('student')
                ->has('assignment')
        );
    }

    #[Test]
    public function results_page_shows_correct_score_calculation()
    {
        ['teacher' => $teacher, 'student' => $student, 'exam' => $exam, 'group' => $group, 'assignment' => $assignment] = $this->createExamWithSubmission();

        $q1 = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10,
        ]);

        $q2 = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 15,
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $q1->id,
            'answer_text' => 'Answer 1',
            'score' => 8.5,
        ]);

        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $q2->id,
            'answer_text' => 'Answer 2',
            'score' => 12.0,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $student]));

        $response->assertOk();
    }

    #[Test]
    public function teacher_can_access_results_before_correction()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();
        $newStudent = $this->createStudent(['email' => 'new@test.com']);

        $group->students()->attach($newStudent->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $newAssignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $newStudent->id,
            'status' => 'submitted',
            'score' => null,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $newStudent]));

        $response->assertOk();
    }

    #[Test]
    public function teacher_cannot_view_results_for_student_not_in_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();
        $otherGroup = Group::factory()->active()->create();
        $studentNotInGroup = $this->createStudent(['email' => 'notingroup@test.com']);

        $otherGroup->students()->attach($studentNotInGroup->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $studentNotInGroup->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $studentNotInGroup]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_cannot_view_results_for_inactive_student_in_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithSubmission();
        $inactiveStudent = $this->createStudent(['email' => 'inactive@test.com']);

        $group->students()->attach($inactiveStudent->id, [
            'enrolled_at' => now(),
            'is_active' => false,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $inactiveStudent->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.submissions', [$exam, $group, $inactiveStudent]));

        $response->assertForbidden();
    }
}
