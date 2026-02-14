<?php

namespace Tests\Feature\Services\Student;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Services\Student\StudentAssessmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ServerSideTimerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private StudentAssessmentService $service;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->service = app(StudentAssessmentService::class);

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

    private function createSupervisedAssessment(array $attributes = []): Assessment
    {
        return Assessment::factory()->supervised()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'duration_minutes' => 60,
            'scheduled_at' => now()->subMinutes(5),
            'settings' => ['is_published' => true],
        ], $attributes));
    }

    private function createHomeworkAssessment(array $attributes = []): Assessment
    {
        return Assessment::factory()->homework()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'settings' => ['is_published' => true],
        ], $attributes));
    }

    public function test_get_or_create_does_not_set_started_at(): void
    {
        $assessment = $this->createSupervisedAssessment();
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');

        $assignment = $this->service->getOrCreateAssignment($student, $assessment);

        $this->assertNull($assignment->started_at);
    }

    public function test_start_assignment_sets_started_at_for_supervised_mode(): void
    {
        $assessment = $this->createSupervisedAssessment();
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');

        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        $this->assertNotNull($assignment->started_at);
        $this->assertEquals('2026-02-13 14:00:00', $assignment->started_at->toDateTimeString());
    }

    public function test_start_assignment_does_not_overwrite_started_at_on_second_call(): void
    {
        $assessment = $this->createSupervisedAssessment();
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $firstAssignment = $this->service->startAssignment($assignment, $assessment);
        $originalStartedAt = $firstAssignment->started_at->toDateTimeString();

        Carbon::setTestNow('2026-02-13 14:30:00');
        $secondAssignment = $this->service->startAssignment($firstAssignment, $assessment);

        $this->assertEquals($originalStartedAt, $secondAssignment->started_at->toDateTimeString());
    }

    public function test_get_or_create_does_not_set_started_at_for_homework_mode(): void
    {
        $assessment = $this->createHomeworkAssessment();
        $student = $this->createStudent();

        $assignment = $this->service->getOrCreateAssignment($student, $assessment);

        $this->assertNull($assignment->started_at);
    }

    public function test_calculate_remaining_seconds_full_time(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        $remaining = $this->service->calculateRemainingSeconds($assignment, $assessment);

        $this->assertEquals(3600, $remaining);
    }

    public function test_calculate_remaining_seconds_decreases_over_time(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 14:20:00');
        $remaining = $this->service->calculateRemainingSeconds($assignment, $assessment);

        $this->assertEquals(2400, $remaining);
    }

    public function test_calculate_remaining_seconds_returns_zero_when_expired(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 15:30:00');
        $remaining = $this->service->calculateRemainingSeconds($assignment, $assessment);

        $this->assertEquals(0, $remaining);
    }

    public function test_calculate_remaining_seconds_returns_null_for_homework(): void
    {
        $assessment = $this->createHomeworkAssessment();
        $student = $this->createStudent();

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $remaining = $this->service->calculateRemainingSeconds($assignment, $assessment);

        $this->assertNull($remaining);
    }

    public function test_is_time_expired_false_within_duration(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 14:30:00');

        $this->assertFalse($this->service->isTimeExpired($assignment, $assessment));
    }

    public function test_is_time_expired_true_after_duration(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 15:00:01');

        $this->assertTrue($this->service->isTimeExpired($assignment, $assessment));
    }

    public function test_is_time_expired_with_grace_period_extends_deadline(): void
    {
        config(['assessment.timing.grace_period_seconds' => 30]);

        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 15:00:15');

        $this->assertTrue($this->service->isTimeExpired($assignment, $assessment, withGrace: false));
        $this->assertFalse($this->service->isTimeExpired($assignment, $assessment, withGrace: true));
    }

    public function test_is_time_expired_false_for_homework_mode(): void
    {
        $assessment = $this->createHomeworkAssessment();
        $student = $this->createStudent();

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $this->assertFalse($this->service->isTimeExpired($assignment, $assessment));
    }

    public function test_auto_submit_if_expired_submits_when_time_up(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 15:05:00');
        $result = $this->service->autoSubmitIfExpired($assignment, $assessment);

        $this->assertTrue($result);

        $assignment->refresh();
        $this->assertNotNull($assignment->submitted_at);
        $this->assertTrue($assignment->forced_submission);
        $this->assertEquals('time_expired', $assignment->security_violation);
        $this->assertEquals(
            '2026-02-13 15:00:00',
            $assignment->submitted_at->toDateTimeString()
        );
    }

    public function test_auto_submit_if_expired_does_not_submit_when_time_remains(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 14:30:00');
        $result = $this->service->autoSubmitIfExpired($assignment, $assessment);

        $this->assertFalse($result);
        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_auto_submit_if_expired_does_not_double_submit(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 60]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        $assignment->update(['submitted_at' => now()]);

        Carbon::setTestNow('2026-02-13 15:05:00');
        $result = $this->service->autoSubmitIfExpired($assignment, $assessment);

        $this->assertFalse($result);
    }

    public function test_auto_submit_sets_submitted_at_to_exact_deadline(): void
    {
        $assessment = $this->createSupervisedAssessment(['duration_minutes' => 30]);
        $student = $this->createStudent();

        Carbon::setTestNow('2026-02-13 14:00:00');
        $assignment = $this->service->getOrCreateAssignment($student, $assessment);
        $assignment = $this->service->startAssignment($assignment, $assessment);

        Carbon::setTestNow('2026-02-13 15:00:00');
        $this->service->autoSubmitIfExpired($assignment, $assessment);

        $assignment->refresh();
        $this->assertEquals(
            '2026-02-13 14:30:00',
            $assignment->submitted_at->toDateTimeString()
        );
    }
}
