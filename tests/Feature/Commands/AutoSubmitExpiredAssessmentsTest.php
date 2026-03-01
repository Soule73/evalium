<?php

namespace Tests\Feature\Commands;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AutoSubmitExpiredAssessmentsTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /**
     * Build a published supervised assessment with one in-progress assignment.
     *
     * @return array{assessment: Assessment, assignment: AssessmentAssignment}
     */
    private function setupInProgressSupervisedAssignment(array $assessmentAttributes = [], array $assignmentAttributes = []): array
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create(array_merge([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subHours(3),
            'duration_minutes' => 60,
            'is_published' => true,
        ], $assessmentAttributes));

        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        $assignment = AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subHours(2),
        ], $assignmentAttributes));

        return compact('assessment', 'assignment');
    }

    public function test_auto_submits_when_per_student_time_expired(): void
    {
        // scheduled_at = -3h, duration = 60min → globally ended 2h ago
        // started_at = -2h → personal deadline = -1h → isTimeExpired = true
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment();

        $this->artisan('assessment:auto-submit-expired')->assertSuccessful();

        $assignment->refresh();
        $this->assertNotNull($assignment->submitted_at);
    }

    public function test_auto_submits_when_assessment_globally_ended_but_per_student_time_not_expired(): void
    {
        // Assessment: scheduled_at = -90min, duration = 60min → ends_at = -30min → globally ended
        // Student started_at = -30min → personal deadline = +30min → isTimeExpired = false
        // hasEnded() fallback should catch this case
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subMinutes(90),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(30),
        ]);

        $this->artisan('assessment:auto-submit-expired')->assertSuccessful();

        $assignment->refresh();
        $this->assertNotNull($assignment->submitted_at);
    }

    public function test_sets_forced_submission_flag_and_security_violation(): void
    {
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment();

        $this->artisan('assessment:auto-submit-expired')->assertSuccessful();

        $this->assertDatabaseHas('assessment_assignments', [
            'id' => $assignment->id,
            'forced_submission' => true,
            'security_violation' => 'time_expired',
        ]);
    }

    public function test_skips_already_submitted_assignments(): void
    {
        $submittedAt = now()->subHour();
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment(
            assignmentAttributes: ['submitted_at' => $submittedAt]
        );

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 0')
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertEquals($submittedAt->toDateTimeString(), $assignment->submitted_at->toDateTimeString());
    }

    public function test_skips_assignments_with_no_started_at(): void
    {
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment(
            assignmentAttributes: ['started_at' => null]
        );

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 0')
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_skips_homework_mode_assessments(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subHours(2),
        ]);

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 0')
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_skips_unpublished_assessments(): void
    {
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment(
            assessmentAttributes: ['is_published' => false]
        );

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 0')
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_skips_not_yet_expired_assignments(): void
    {
        // Assessment ends in the future, per-student time not expired either
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subMinutes(10),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 0')
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_dry_run_does_not_persist_submissions(): void
    {
        ['assignment' => $assignment] = $this->setupInProgressSupervisedAssignment();

        $this->artisan('assessment:auto-submit-expired', ['--dry-run' => true])
            ->assertSuccessful();

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_dry_run_still_reports_would_submit_count(): void
    {
        $this->setupInProgressSupervisedAssignment();

        $this->artisan('assessment:auto-submit-expired', ['--dry-run' => true])
            ->expectsOutputToContain('Submitted: 1')
            ->assertSuccessful();
    }

    public function test_handles_multiple_expired_assignments(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subHours(3),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        foreach (range(1, 3) as $_) {
            $student = $this->createStudent();
            $enrollment = Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
            ]);

            AssessmentAssignment::factory()->create([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'started_at' => now()->subHours(2),
            ]);
        }

        $this->artisan('assessment:auto-submit-expired')
            ->expectsOutputToContain('Submitted: 3')
            ->assertSuccessful();

        $this->assertEquals(
            3,
            AssessmentAssignment::whereNotNull('submitted_at')->count()
        );
    }
}
