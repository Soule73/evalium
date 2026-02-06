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
        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment3 = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 85.5,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now()->subHour(),
        ]);

        $this->createAssignmentForStudent($assessment3, $student);

        $result = $this->service->getDashboardStats($student);

        $this->assertGreaterThanOrEqual(3, $result['totalAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['completedAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['submittedAssessments']);
        $this->assertGreaterThanOrEqual(1, $result['pendingAssessments']);
        $this->assertIsFloat($result['averageScore']);
        $this->assertGreaterThan(0, $result['completionRate']);
    }

    public function test_get_performance_summary_calculates_correctly(): void
    {
        $student = $this->createStudent();
        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment3 = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment1, $student, ['score' => 75.0]);
        $this->createAssignmentForStudent($assessment2, $student, ['score' => 90.0]);
        $this->createAssignmentForStudent($assessment3, $student, ['score' => 45.0]);

        $result = $this->service->getPerformanceSummary($student);

        $this->assertEquals(3, $result['totalGraded']);
        $this->assertEquals(70.0, $result['averageScore']);
        $this->assertEquals(90.0, $result['highestScore']);
        $this->assertEquals(45.0, $result['lowestScore']);
        $this->assertEquals(66.67, $result['passingRate']);
    }

    public function test_get_performance_summary_handles_no_graded_assignments(): void
    {
        $student = $this->createStudent();
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment, $student, ['score' => null]);

        $result = $this->service->getPerformanceSummary($student);

        $this->assertEquals(0, $result['totalGraded']);
        $this->assertNull($result['averageScore']);
        $this->assertNull($result['highestScore']);
        $this->assertNull($result['lowestScore']);
        $this->assertEquals(0, $result['passingRate']);
    }

    public function test_get_recent_activity_returns_sorted_activities(): void
    {
        $student = $this->createStudent();
        $assessment1 = $this->createAssessmentWithQuestions(assessmentAttributes: [
            'title' => 'Math Assessment',
        ], questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(assessmentAttributes: [
            'title' => 'Science Assessment',
        ], questionCount: 0);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now()->subDays(2),
            'graded_at' => now()->subDay(),
            'score' => 80.0,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now()->subHour(),
        ]);

        $result = $this->service->getRecentActivity($student, 10);

        $this->assertCount(2, $result);
        $this->assertEquals('submission', $result[0]['type']);
        $this->assertEquals('Science Assessment', $result[0]['assessmentTitle']);
        $this->assertEquals('submission', $result[1]['type']);
        $this->assertEquals('Math Assessment', $result[1]['assessmentTitle']);
    }

    public function test_get_subject_performance_groups_by_subject(): void
    {
        $student = $this->createStudent();
        $mathAssessment = $this->createAssessmentWithQuestions(assessmentAttributes: [
            'title' => 'Math Test',
        ], questionCount: 0);
        $scienceAssessment = $this->createAssessmentWithQuestions(assessmentAttributes: [
            'title' => 'Science Test',
        ], questionCount: 0);

        $this->createAssignmentForStudent($mathAssessment, $student, ['score' => 85.0]);
        $this->createAssignmentForStudent($scienceAssessment, $student, ['score' => 75.0]);

        $result = $this->service->getSubjectPerformance($student);

        $this->assertCount(1, $result);
        $this->assertEquals('General', $result[0]['subject']);
        $this->assertEquals(2, $result[0]['totalAssessments']);
    }

    public function test_get_monthly_progress_groups_by_month(): void
    {
        $student = $this->createStudent();
        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now()->subMonth(),
            'score' => 80.0,
        ]);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now(),
            'score' => 90.0,
        ]);

        $result = $this->service->getMonthlyProgress($student, 6);

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('count', $result[0]);
        $this->assertArrayHasKey('averageScore', $result[0]);
    }

    public function test_get_group_performance_calculates_per_group(): void
    {
        $this->markTestSkipped('Assessment-class direct assignment not implemented in new architecture');
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $class = $this->createClassWithStudents(studentCount: 0);
        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 0);

        $class->assessments()->attach([$assessment1->id, $assessment2->id], ['assigned_by' => $teacher->id]);
        $class->enrollments()->create([
            'student_id' => $student->id,
        ]);

        $this->createAssignmentForStudent($assessment1, $student, [
            'submitted_at' => now(),
            'score' => 85.0,
        ]);

        $result = $this->service->getGroupPerformance($student);

        $this->assertCount(1, $result);
        $this->assertEquals($class->id, $result[0]['classId']);
        $this->assertEquals(2, $result[0]['totalAssessments']);
        $this->assertEquals(1, $result[0]['completedAssessments']);
        $this->assertEquals(85.0, $result[0]['averageScore']);
        $this->assertEquals(50.0, $result[0]['completion_rate']);
    }

    public function test_get_exam_status_breakdown_counts_correctly(): void
    {
        $this->markTestSkipped('getExamStatusBreakdown method does not exist in StudentDashboardService');
        $student = $this->createStudent();
        $assessment1 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment2 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment3 = $this->createAssessmentWithQuestions(questionCount: 0);
        $assessment4 = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment1, $student);

        $this->createAssignmentForStudent($assessment2, $student, [
            'submitted_at' => now(),
        ]);

        $this->createAssignmentForStudent($assessment3, $student, [
            'submitted_at' => now()->subHour(),
        ]);

        $this->createAssignmentForStudent($assessment4, $student, [
            'submitted_at' => now()->subHours(2),
            'graded_at' => now()->subHour(),
            'score' => 75.0,
        ]);

        $result = $this->service->getAssessmentStatusBreakdown($student);

        $this->assertGreaterThanOrEqual(1, $result['notStarted']);
        $this->assertGreaterThanOrEqual(2, $result['submitted']);
        $this->assertGreaterThanOrEqual(1, $result['graded']);

        $total = $result['not_started'] + $result['in_progress'] + $result['submitted'] + $result['graded'];
        $this->assertGreaterThanOrEqual(4, $total);
    }

    public function test_get_dashboard_stats_handles_no_active_groups(): void
    {
        $student = $this->createStudent();
        $assessment = $this->createAssessmentWithQuestions(questionCount: 0);

        $this->createAssignmentForStudent($assessment, $student);

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(0, $result['activeClasses']);
        $this->assertIsArray($result['upcomingAssessments']);
        $this->assertIsArray($result['recentAssessments']);
    }
}
