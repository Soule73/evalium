<?php

namespace Tests\Feature\Services\Core;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Services\Core\AssessmentStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AssessmentStatsServiceTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private AssessmentStatsService $service;

    private Assessment $assessment;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->service = app(AssessmentStatsService::class);

        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
        ]);

        $student = $this->createStudent();
        $this->enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $classModel->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'is_published' => true,
        ]);
    }

    public function test_stats_include_in_progress_and_not_started(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        $stats = $this->service->calculateAssessmentStats($this->assessment->id);

        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertArrayHasKey('not_started', $stats);
        $this->assertArrayHasKey('not_submitted', $stats);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(0, $stats['not_started']);
        $this->assertEquals(1, $stats['not_submitted']);
    }

    public function test_stats_not_submitted_equals_in_progress_plus_not_started(): void
    {
        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->enrollment->class_id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => now(),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollment2->id,
        ]);

        $stats = $this->service->calculateAssessmentStats($this->assessment->id);

        $this->assertEquals(2, $stats['total_assigned']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['not_started']);
        $this->assertEquals(2, $stats['not_submitted']);
        $this->assertEquals($stats['in_progress'] + $stats['not_started'], $stats['not_submitted']);
    }

    public function test_stats_full_lifecycle(): void
    {
        $students = [];
        $enrollments = [];
        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->createStudent();
            $enrollments[$i] = Enrollment::create([
                'student_id' => $students[$i]->id,
                'class_id' => $this->enrollment->class_id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
        }

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollments[0]->id,
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollments[1]->id,
            'started_at' => now(),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollments[2]->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollments[3]->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'graded_at' => now(),
            'score' => 80,
        ]);

        $stats = $this->service->calculateAssessmentStats($this->assessment->id);

        $this->assertEquals(5, $stats['total_assigned']);
        $this->assertEquals(2, $stats['not_started']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['submitted']);
        $this->assertEquals(1, $stats['graded']);
        $this->assertEquals(3, $stats['not_submitted']);
        $this->assertEquals(80.0, $stats['average_score']);
        $this->assertEquals(20.0, $stats['completion_rate']);
    }
}
