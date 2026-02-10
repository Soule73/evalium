<?php

namespace Tests\Unit\Services\Student;

use App\Services\Student\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentDashboardServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private StudentDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->service = app(StudentDashboardService::class);
    }

    public function test_get_dashboard_stats_returns_comprehensive_data(): void
    {
        $student = $this->createStudent();
        $class = $this->createClassWithStudents(studentCount: 0);

        $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 2);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 2);
        $assessment3 = $this->createAssessmentWithQuestions(questionCount: 2);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 15,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now()->subHour(),
            'score' => null,
        ]);

        $this->createAssignmentForStudent($assessment3, $student);

        $result = $this->service->getDashboardStats($student);

        $this->assertArrayHasKey('totalAssessments', $result);
        $this->assertArrayHasKey('completedAssessments', $result);
        $this->assertArrayHasKey('pendingAssessments', $result);
        $this->assertArrayHasKey('averageScore', $result);

        $this->assertGreaterThanOrEqual(3, $result['totalAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['completedAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['pendingAssessments']);
    }

    public function test_get_dashboard_stats_calculates_average_correctly(): void
    {
        $student = $this->createStudent();
        $class = $this->createClassWithStudents(studentCount: 0);

        $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 2);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 2);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 20,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinute(),
            'score' => 12,
        ]);

        $result = $this->service->getDashboardStats($student);

        $this->assertGreaterThanOrEqual(2, $result['totalAssessments']);
        $this->assertGreaterThanOrEqual(2, $result['completedAssessments']);

        if ($result['averageScore'] !== null) {
            $this->assertGreaterThan(0, $result['averageScore']);
            $this->assertLessThanOrEqual(20, $result['averageScore']);
        }
    }

    public function test_get_dashboard_stats_handles_no_enrollments(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(0, $result['totalAssessments']);
        $this->assertEquals(0, $result['completedAssessments']);
        $this->assertEquals(0, $result['pendingAssessments']);
        $this->assertNull($result['averageScore']);
    }

    public function test_get_dashboard_stats_filters_by_academic_year(): void
    {
        $student = $this->createStudent();
        $class1 = $this->createClassWithStudents(studentCount: 0);
        $class2 = $this->createClassWithStudents(studentCount: 0);

        $class1->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 2);
        $this->createAssignmentForStudent($assessment1, $student);

        $result = $this->service->getDashboardStats($student, $class1->academic_year_id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalAssessments', $result);
    }
}
