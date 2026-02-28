<?php

namespace Tests\Feature\Teacher;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

/**
 * Tests the assignment reassignment feature and auto-grade zero behavior.
 */
class AssignmentReassignTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $teacher;

    private User $student;

    private Assessment $homeworkAssessment;

    private Assessment $supervisedAssessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->homeworkAssessment = $this->createAssessmentWithQuestions($this->teacher, [
            'delivery_mode' => 'homework',
            'due_date' => now()->subHours(2),
        ]);

        $this->supervisedAssessment = $this->createAssessmentWithQuestions($this->teacher, [
            'delivery_mode' => 'supervised',
            'scheduled_at' => now()->subHours(3),
            'duration_minutes' => 60,
        ]);

        $this->ensureStudentEnrolled($this->student, $this->homeworkAssessment);
        $this->ensureStudentEnrolled($this->student, $this->supervisedAssessment);
    }

    #[Test]
    public function auto_grades_zero_on_grade_page_visit_when_no_responses(): void
    {
        $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

        $this->assertNull($assignment->graded_at);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Grade')
                    ->where('gradingState.no_responses', true)
                    ->where('gradingState.correction_locked', true)
            );

        $this->assertNotNull($assignment->fresh()->graded_at);
    }

    #[Test]
    public function does_not_auto_grade_when_assignment_has_responses(): void
    {
        $assignment = $this->createSubmittedAssignment($this->homeworkAssessment, $this->student);

        $this->homeworkAssessment->loadMissing('questions');
        foreach ($this->homeworkAssessment->questions as $question) {
            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'score' => 0,
            ]);
        }

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Grade')
                    ->where('gradingState.no_responses', false)
                    ->where('gradingState.correction_locked', false)
            );
    }

    #[Test]
    public function can_reassign_homework_with_no_responses(): void
    {
        $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => 'Student had technical issues'])
            ->assertOk()
            ->assertJson(['message' => __('messages.assignment_reassigned')]);

        $assignment->refresh();
        $this->assertNull($assignment->started_at);
        $this->assertNull($assignment->submitted_at);
        $this->assertNull($assignment->graded_at);
    }

    #[Test]
    public function can_reassign_supervised_not_started_with_no_responses(): void
    {
        $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->supervisedAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => 'Student was absent'])
            ->assertOk()
            ->assertJson(['message' => __('messages.assignment_reassigned')]);

        $assignment->refresh();
        $this->assertNull($assignment->started_at);
        $this->assertNull($assignment->graded_at);
    }

    #[Test]
    public function cannot_reassign_supervised_started_assignment(): void
    {
        $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
            'started_at' => now()->subHours(3),
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->supervisedAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => 'Student wants another try'])
            ->assertStatus(422);
    }

    #[Test]
    public function cannot_reassign_assignment_with_responses(): void
    {
        $assignment = $this->createSubmittedAssignment($this->homeworkAssessment, $this->student);

        $this->homeworkAssessment->loadMissing('questions');
        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $this->homeworkAssessment->questions->first()->id,
            'score' => 5,
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => 'Test'])
            ->assertStatus(422);
    }

    #[Test]
    public function reassign_requires_reason(): void
    {
        $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => ''])
            ->assertStatus(422);
    }

    #[Test]
    public function reassign_clears_auto_graded_data(): void
    {
        $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertOk();

        $this->assertNotNull($assignment->fresh()->graded_at);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.assessments.reassign', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]), ['reason' => 'Giving the student another chance'])
            ->assertOk();

        $assignment->refresh();
        $this->assertNull($assignment->graded_at);
        $this->assertNull($assignment->started_at);
        $this->assertNull($assignment->submitted_at);
    }

    #[Test]
    public function grade_page_shows_reassign_info_for_homework(): void
    {
        $assignment = $this->createAssignmentForStudent($this->homeworkAssessment, $this->student);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->homeworkAssessment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Grade')
                    ->where('gradingState.can_reassign', true)
                    ->where('gradingState.reassign_reason', null)
            );
    }

    #[Test]
    public function grade_page_shows_reassign_blocked_for_supervised_started(): void
    {
        $assignment = $this->createAssignmentForStudent($this->supervisedAssessment, $this->student, [
            'started_at' => now()->subHours(3),
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->supervisedAssessment->id,
                'assignment' => $assignment->id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Assessments/Grade')
                    ->where('gradingState.can_reassign', false)
                    ->where('gradingState.reassign_reason', 'supervised_questions_exposed')
            );
    }

    private function ensureStudentEnrolled(User $student, Assessment $assessment): void
    {
        $assessment->loadMissing('classSubject');

        \App\Models\Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'class_id' => $assessment->classSubject->class_id],
            ['enrolled_at' => now(), 'status' => 'active']
        );
    }
}
