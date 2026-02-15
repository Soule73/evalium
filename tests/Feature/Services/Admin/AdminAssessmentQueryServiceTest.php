<?php

namespace Tests\Feature\Services\Admin;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;
use App\Services\Admin\AdminAssessmentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AdminAssessmentQueryServiceTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private AdminAssessmentQueryService $service;

    private ClassSubject $classSubject;

    private User $student;

    private Enrollment $enrollment;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->service = app(AdminAssessmentQueryService::class);

        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
        ]);

        $this->student = $this->createStudent();
        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $classModel->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'settings' => ['is_published' => true],
        ]);
    }

    public function test_filter_not_submitted_returns_assignments_without_started_at(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $this->service->getAssignmentsForStudent(
            $this->student,
            ['status' => 'not_submitted']
        );

        $this->assertCount(1, $result);
        $this->assertNull($result->first()->started_at);
    }

    public function test_filter_in_progress_returns_started_not_submitted_assignments(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $this->service->getAssignmentsForStudent(
            $this->student,
            ['status' => 'in_progress']
        );

        $this->assertCount(1, $result);
        $this->assertNotNull($result->first()->started_at);
        $this->assertNull($result->first()->submitted_at);
    }

    public function test_filter_submitted_returns_submitted_not_graded_assignments(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $this->service->getAssignmentsForStudent(
            $this->student,
            ['status' => 'submitted']
        );

        $this->assertCount(1, $result);
        $this->assertNotNull($result->first()->submitted_at);
        $this->assertNull($result->first()->graded_at);
    }

    public function test_filter_graded_returns_graded_assignments(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'graded_at' => now(),
            'score' => 85,
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $this->service->getAssignmentsForStudent(
            $this->student,
            ['status' => 'graded']
        );

        $this->assertCount(1, $result);
        $this->assertNotNull($result->first()->graded_at);
    }

    public function test_filters_are_mutually_exclusive(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $secondAssessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'settings' => ['is_published' => true],
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $secondAssessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection $notSubmitted */
        $notSubmitted = $this->service->getAssignmentsForStudent($this->student, ['status' => 'not_submitted']);

        /** @var \Illuminate\Database\Eloquent\Collection $inProgress */
        $inProgress = $this->service->getAssignmentsForStudent($this->student, ['status' => 'in_progress']);

        $this->assertCount(1, $notSubmitted);
        $this->assertCount(1, $inProgress);

        $notSubmittedIds = $notSubmitted->pluck('id')->toArray();
        $inProgressIds = $inProgress->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($notSubmittedIds, $inProgressIds));
    }

    public function test_student_stats_counts_in_progress_correctly(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        $stats = $this->service->getStudentAssignmentStats($this->student);

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['graded']);
    }

    public function test_student_stats_returns_complete_breakdown(): void
    {
        $assessments = [];
        for ($i = 0; $i < 4; $i++) {
            $assessments[] = Assessment::factory()->supervised()->create([
                'class_subject_id' => $this->classSubject->id,
                'teacher_id' => $this->classSubject->teacher_id,
                'settings' => ['is_published' => true],
            ]);
        }

        AssessmentAssignment::create([
            'assessment_id' => $assessments[0]->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessments[1]->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessments[2]->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'graded_at' => now(),
            'score' => 90,
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessments[3]->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $stats = $this->service->getStudentAssignmentStats($this->student);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['graded']);
        $this->assertEquals(90.0, $stats['average_score']);
    }
}
