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
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class TeacherExceptionHandlingTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

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
     * @return array{teacher: \App\Models\User, student: \App\Models\User, assessment: Assessment, assignment: AssessmentAssignment}
     */
    private function createInterruptedSupervisedAssignment(array $assessmentOverrides = [], array $assignmentOverrides = []): array
    {
        $teacher = $this->createTeacher();

        $this->classSubject->update(['teacher_id' => $teacher->id]);

        $student = $this->createStudent();
        ClassModel::find($this->classSubject->class_id)
            ->enrollments()
            ->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);

        $assessment = Assessment::factory()->supervised()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $teacher->id,
            'duration_minutes' => 60,
            'scheduled_at' => now()->subMinutes(30),
            'settings' => ['is_published' => true],
        ], $assessmentOverrides));

        Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
            'points' => 10,
        ]);

        $enrollment = \App\Models\Enrollment::where('student_id', $student->id)
            ->where('class_id', $this->classSubject->class_id)
            ->first();

        $assignment = AssessmentAssignment::create(array_merge([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
            'forced_submission' => true,
            'security_violation' => 'tab_switch',
        ], $assignmentOverrides));

        return compact('teacher', 'student', 'assessment', 'assignment');
    }

    public function test_teacher_can_reopen_interrupted_supervised_assignment(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        ['teacher' => $teacher, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 09:50:00')]
            );

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Power outage during exam',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'remaining_seconds']);

        $assignment->refresh();

        $this->assertNull($assignment->submitted_at);
        $this->assertFalse((bool) $assignment->forced_submission);
        $this->assertNull($assignment->security_violation);
        $this->assertNotNull($assignment->started_at);
    }

    public function test_reopened_assignment_has_correct_remaining_time(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        ['teacher' => $teacher, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 09:50:00')]
            );

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Browser crash',
            ]);

        $response->assertOk();

        $remainingSeconds = $response->json('remaining_seconds');

        $elapsedSeconds = Carbon::parse('2026-06-15 09:30:00')->diffInSeconds(Carbon::parse('2026-06-15 10:00:00'));
        $expectedRemaining = (60 * 60) - $elapsedSeconds;

        $this->assertEquals($expectedRemaining, $remainingSeconds);
    }

    public function test_cannot_reopen_homework_assignment(): void
    {
        $teacher = $this->createTeacher();
        $this->classSubject->update(['teacher_id' => $teacher->id]);

        $student = $this->createStudent();
        $enrollment = ClassModel::find($this->classSubject->class_id)
            ->enrollments()
            ->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);

        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addDays(7),
            'settings' => ['is_published' => true],
        ]);

        Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
            'points' => 10,
        ]);

        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
            'forced_submission' => true,
        ]);

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Some reason',
            ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => __('messages.assignment_cannot_reopen_not_supervised')]);
    }

    public function test_cannot_reopen_when_time_fully_elapsed(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        ['teacher' => $teacher, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 10:00:00')]
            );

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Late request',
            ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => __('messages.assignment_cannot_reopen_time_fully_elapsed')]);
    }

    public function test_cannot_reopen_assignment_not_interrupted(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        ['teacher' => $teacher, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                [
                    'started_at' => Carbon::parse('2026-06-15 09:30:00'),
                    'submitted_at' => null,
                    'forced_submission' => false,
                    'security_violation' => null,
                ]
            );

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Some reason',
            ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => __('messages.assignment_cannot_reopen_not_interrupted')]);
    }

    public function test_reopen_logs_audit_entry(): void
    {
        Log::spy();

        Carbon::setTestNow('2026-06-15 10:00:00');

        ['teacher' => $teacher, 'student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 09:50:00')]
            );

        $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Power outage during exam',
            ]);

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($assignment, $assessment, $teacher) {
                return $message === 'Assignment reopened by teacher'
                    && $context['assignment_id'] === $assignment->id
                    && $context['assessment_id'] === $assessment->id
                    && $context['enrollment_id'] === $assignment->enrollment_id
                    && $context['teacher_id'] === $teacher->id
                    && $context['reason'] === 'Power outage during exam';
            })
            ->once();
    }

    public function test_reopen_requires_reason(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        ['teacher' => $teacher, 'assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 09:50:00')]
            );

        $response = $this->actingAs($teacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_unauthorized_teacher_cannot_reopen_assignment(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        ['assessment' => $assessment, 'assignment' => $assignment] =
            $this->createInterruptedSupervisedAssignment(
                ['duration_minutes' => 60, 'scheduled_at' => Carbon::parse('2026-06-15 09:00:00')],
                ['started_at' => Carbon::parse('2026-06-15 09:30:00'), 'submitted_at' => Carbon::parse('2026-06-15 09:50:00')]
            );

        $otherTeacher = $this->createTeacher();

        $response = $this->actingAs($otherTeacher)
            ->postJson(route('teacher.assessments.reopen', [$assessment, $assignment]), [
                'reason' => 'Some reason',
            ]);

        $response->assertForbidden();
    }
}
