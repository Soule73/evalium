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

class SupervisedModeHardeningTest extends TestCase
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
    private function createEnrolledStudentWithSupervisedAssessment(array $assessmentOverrides = []): array
    {
        $student = $this->createStudent();
        $classModel = ClassModel::find($this->classSubject->class_id);
        $enrollment = $classModel->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->supervised()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'duration_minutes' => 60,
            'scheduled_at' => now()->subMinutes(5),
            'settings' => ['is_published' => true],
        ], $assessmentOverrides));

        Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
            'points' => 10,
        ]);

        return ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment];
    }

    /**
     * @return array{student: \App\Models\User, assessment: Assessment, enrollment: Enrollment}
     */
    private function createEnrolledStudentWithHomeworkAssessment(array $assessmentOverrides = []): array
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
            'settings' => ['is_published' => true],
        ], $assessmentOverrides));

        Question::factory()->create([
            'assessment_id' => $assessment->id,
            'type' => 'text',
            'points' => 10,
        ]);

        return ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment];
    }

    public function test_supervised_cannot_retake_after_submission(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithSupervisedAssessment();

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertRedirect(route('student.assessments.results', $assessment));
    }

    public function test_supervised_take_auto_submits_when_time_expired(): void
    {
        Carbon::setTestNow('2026-02-13 14:00:00');

        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithSupervisedAssessment([
            'duration_minutes' => 60,
            'scheduled_at' => Carbon::parse('2026-02-13 12:00:00'),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => Carbon::parse('2026-02-13 12:30:00'),
        ]);

        Carbon::setTestNow('2026-02-13 14:00:00');

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertRedirect(route('student.assessments.results', $assessment));

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNotNull($assignment->submitted_at);
        $this->assertTrue((bool) $assignment->forced_submission);
    }

    public function test_supervised_security_violation_triggers_auto_submit(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithSupervisedAssessment();

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.security-violation', $assessment), [
                'violation_type' => 'tab_switch',
                'violation_details' => 'Student switched tabs',
            ]);

        $response->assertOk();

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNotNull($assignment->submitted_at);
        $this->assertTrue((bool) $assignment->forced_submission);
        $this->assertStringContainsString('tab_switch', $assignment->security_violation);
    }

    public function test_security_violation_rejected_for_homework_mode(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithHomeworkAssessment();

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.security-violation', $assessment), [
                'violation_type' => 'tab_switch',
            ]);

        $response->assertStatus(422);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->first();

        $this->assertNull($assignment->submitted_at);
    }

    public function test_supervised_remaining_seconds_correct_after_partial_elapsed(): void
    {
        Carbon::setTestNow('2026-02-13 14:00:00');

        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithSupervisedAssessment([
            'duration_minutes' => 60,
            'scheduled_at' => Carbon::parse('2026-02-13 13:30:00'),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => Carbon::parse('2026-02-13 13:40:00'),
        ]);

        Carbon::setTestNow('2026-02-13 14:00:00');

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Assessments/Take')
                ->where('remainingSeconds', 2400)
        );
    }

    public function test_terminate_for_violation_ignored_for_homework_mode(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithHomeworkAssessment();

        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $service = app(\App\Services\Student\StudentAssessmentService::class);
        $result = $service->terminateForViolation($assignment, $assessment, 'tab_switch');

        $this->assertFalse($result);

        $assignment->refresh();
        $this->assertNull($assignment->submitted_at);
    }

    public function test_supervised_auto_submit_expired_sets_submitted_at_to_deadline(): void
    {
        Carbon::setTestNow('2026-02-13 15:30:00');

        ['student' => $student, 'assessment' => $assessment, 'enrollment' => $enrollment] = $this->createEnrolledStudentWithSupervisedAssessment([
            'duration_minutes' => 60,
            'scheduled_at' => Carbon::parse('2026-02-13 13:00:00'),
        ]);

        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => Carbon::parse('2026-02-13 14:00:00'),
        ]);

        $service = app(\App\Services\Student\StudentAssessmentService::class);
        $result = $service->autoSubmitIfExpired($assignment, $assessment);

        $this->assertTrue($result);

        $assignment->refresh();
        $this->assertEquals(
            '2026-02-13 15:00:00',
            $assignment->submitted_at->toDateTimeString()
        );
        $this->assertEquals('time_expired', $assignment->security_violation);
    }
}
