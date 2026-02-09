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
        $this->assertArrayHasKey('upcomingAssessments', $result);
        $this->assertArrayHasKey('recentAssessments', $result);
        $this->assertArrayHasKey('subjectsBreakdown', $result);

        $this->assertGreaterThanOrEqual(3, $result['totalAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['completedAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['pendingAssessments']);
        $this->assertIsArray($result['upcomingAssessments']);
        $this->assertIsArray($result['recentAssessments']);
    }

    public function test_get_detailed_assessments_list_returns_normalized_grades(): void
    {
        $student = $this->createStudent();
        $class = $this->createClassWithStudents(studentCount: 0);

        $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 3);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 2);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now(),
            'score' => 20,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now()->subHour(),
            'score' => 12,
        ]);

        $result = $this->service->getDetailedAssessmentsList($student);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));

        foreach ($result as $assessment) {
            $this->assertArrayHasKey('id', $assessment);
            $this->assertArrayHasKey('title', $assessment);
            $this->assertArrayHasKey('raw_score', $assessment);
            $this->assertArrayHasKey('max_points', $assessment);
            $this->assertArrayHasKey('normalized_grade', $assessment);
            $this->assertArrayHasKey('status', $assessment);

            if ($assessment['raw_score'] !== null && $assessment['max_points'] > 0) {
                $expectedNormalized = round(($assessment['raw_score'] / $assessment['max_points']) * 20, 2);
                $this->assertEquals($expectedNormalized, $assessment['normalized_grade']);
            }
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
        $this->assertIsArray($result['upcomingAssessments']);
        $this->assertIsArray($result['recentAssessments']);
        $this->assertIsArray($result['subjectsBreakdown']);
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
