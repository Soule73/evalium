<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Question;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class HomeworkStudentFlowTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{student: \App\Models\User, assessment: Assessment, enrollment: Enrollment}
     */
    private function createEnrolledStudentWithHomework(array $assessmentOverrides = []): array
    {
        $student = $this->createStudent();
        $classModel = ClassModel::find($this->classSubject->class_id);
        $enrollment = $classModel->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->homework()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->addDays(7),
            'scheduled_at' => now()->subDay(),
            'is_published' => true,
        ], $assessmentOverrides));

        Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
            'points' => 10,
        ]);

        return ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment];
    }

    public function test_homework_take_renders_work_page(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework();

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Student/Assessments/Work'));
    }

    public function test_homework_sets_started_at_on_take(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework();

        $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertNotNull($assignment->started_at);
    }

    public function test_homework_allows_multi_session_access(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework();

        $response1 = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));
        $response1->assertOk();

        $response2 = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));
        $response2->assertOk();
        $response2->assertInertia(fn ($page) => $page->component('Student/Assessments/Work'));
    }

    public function test_homework_save_answers_works(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework();

        $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $question = $assessment->questions->first();

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.save-answers', $assessment), [
                'answers' => [$question->id => 'My homework answer'],
            ]);

        $response->assertOk();

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertCount(1, $assignment->answers);
        $this->assertEquals('My homework answer', $assignment->answers->first()->answer_text);
    }

    public function test_homework_submit_before_due_date_succeeds(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework([
            'due_date' => now()->addDays(7),
        ]);

        $question = $assessment->questions->first();

        $response = $this->actingAs($student)
            ->post(route('student.assessments.submit', $assessment), [
                'answers' => [$question->id => 'My homework answer'],
            ]);

        $response->assertRedirect(route('student.assessments.results', $assessment));

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNotNull($assignment->submitted_at);
    }

    public function test_homework_submit_after_due_date_rejected(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework([
            'due_date' => Carbon::parse('2026-05-30 23:59:59'),
            'scheduled_at' => Carbon::parse('2026-05-20 00:00:00'),
        ]);

        $question = $assessment->questions->first();

        $response = $this->actingAs($student)
            ->post(route('student.assessments.submit', $assessment), [
                'answers' => [$question->id => 'Late homework'],
            ]);

        $response->assertRedirect(route('student.assessments.show', $assessment));
        $response->assertSessionHas('error');

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNull($assignment?->submitted_at);
    }

    public function test_homework_save_answers_after_due_date_rejected(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework([
            'due_date' => Carbon::parse('2026-05-30 23:59:59'),
            'scheduled_at' => Carbon::parse('2026-05-20 00:00:00'),
        ]);

        $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $question = $assessment->questions->first();

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.save-answers', $assessment), [
                'answers' => [$question->id => 'Late answer'],
            ]);

        $response->assertStatus(409);
    }

    public function test_homework_submitted_redirects_to_show(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithHomework();

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertRedirect(route('student.assessments.show', $assessment));
    }

    public function test_homework_remaining_seconds_is_null(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework();

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Work')
                ->where('remainingSeconds', null)
        );
    }

    public function test_homework_allow_late_submission_bypasses_due_date(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        ['student' => $student, 'assessment' => $assessment] = $this->createEnrolledStudentWithHomework([
            'due_date' => Carbon::parse('2026-05-30 23:59:59'),
            'scheduled_at' => Carbon::parse('2026-05-20 00:00:00'),
            'is_published' => true, 'allow_late_submission' => true,
        ]);

        $question = $assessment->questions->first();

        $response = $this->actingAs($student)
            ->post(route('student.assessments.submit', $assessment), [
                'answers' => [$question->id => 'Late but allowed'],
            ]);

        $response->assertRedirect(route('student.assessments.results', $assessment));

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNotNull($assignment->submitted_at);
    }
}
