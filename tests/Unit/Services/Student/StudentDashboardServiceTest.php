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
        $exam1 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam3 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam1, $student, [
            'status' => 'graded',
            'submitted_at' => now(),
            'score' => 85.5,
        ]);

        $this->createAssignmentForStudent($exam2, $student, [
            'started_at' => now()->subHour(),
            'submitted_at' => null,
        ]);

        $this->createAssignmentForStudent($exam3, $student, [
            'started_at' => null,
        ]);

        $result = $this->service->getDashboardStats($student);

        $this->assertGreaterThanOrEqual(3, $result['total_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['completed_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['in_progress_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['not_started_assignments']);
        $this->assertIsFloat($result['average_score']);
        $this->assertGreaterThan(0, $result['completion_rate']);
    }

    public function test_get_performance_summary_calculates_correctly(): void
    {
        $student = $this->createStudent();
        $exam1 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam3 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam1, $student, ['score' => 75.0]);
        $this->createAssignmentForStudent($exam2, $student, ['score' => 90.0]);
        $this->createAssignmentForStudent($exam3, $student, ['score' => 45.0]);

        $result = $this->service->getPerformanceSummary($student);

        $this->assertEquals(3, $result['total_graded']);
        $this->assertEquals(70.0, $result['average_score']);
        $this->assertEquals(90.0, $result['highest_score']);
        $this->assertEquals(45.0, $result['lowest_score']);
        $this->assertEquals(66.67, $result['passing_rate']);
    }

    public function test_get_performance_summary_handles_no_graded_assignments(): void
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam, $student, ['score' => null]);

        $result = $this->service->getPerformanceSummary($student);

        $this->assertEquals(0, $result['total_graded']);
        $this->assertNull($result['average_score']);
        $this->assertNull($result['highest_score']);
        $this->assertNull($result['lowest_score']);
        $this->assertEquals(0, $result['passing_rate']);
    }

    public function test_get_recent_activity_returns_sorted_activities(): void
    {
        $student = $this->createStudent();
        $exam1 = $this->createExamWithQuestions(examAttributes: [
            'title' => 'Math Exam',
            'is_active' => true,
        ], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: [
            'title' => 'Science Exam',
            'is_active' => true,
        ], questionCount: 0);

        $this->createAssignmentForStudent($exam1, $student, [
            'submitted_at' => now()->subDays(2),
            'score' => 80.0,
            'status' => 'graded',
        ]);

        $this->createAssignmentForStudent($exam2, $student, [
            'started_at' => now()->subHour(),
            'submitted_at' => null,
        ]);

        $result = $this->service->getRecentActivity($student, 10);

        $this->assertCount(2, $result);
        $this->assertEquals('started', $result[0]['type']);
        $this->assertEquals('Science Exam', $result[0]['exam_title']);
        $this->assertEquals('submission', $result[1]['type']);
        $this->assertEquals('Math Exam', $result[1]['exam_title']);
    }

    public function test_get_subject_performance_groups_by_subject(): void
    {
        $student = $this->createStudent();
        $mathExam = $this->createExamWithQuestions(examAttributes: [
            'title' => 'Math Test',
            'is_active' => true,
        ], questionCount: 0);
        $scienceExam = $this->createExamWithQuestions(examAttributes: [
            'title' => 'Science Test',
            'is_active' => true,
        ], questionCount: 0);

        $this->createAssignmentForStudent($mathExam, $student, ['score' => 85.0]);
        $this->createAssignmentForStudent($scienceExam, $student, ['score' => 75.0]);

        $result = $this->service->getSubjectPerformance($student);

        $this->assertCount(1, $result);
        $this->assertEquals('General', $result[0]['subject']);
        $this->assertEquals(2, $result[0]['total_exams']);
    }

    public function test_get_monthly_progress_groups_by_month(): void
    {
        $student = $this->createStudent();
        $exam1 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam1, $student, [
            'submitted_at' => now()->subMonth(),
            'score' => 80.0,
        ]);

        $this->createAssignmentForStudent($exam2, $student, [
            'submitted_at' => now(),
            'score' => 90.0,
        ]);

        $result = $this->service->getMonthlyProgress($student, 6);

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('count', $result[0]);
        $this->assertArrayHasKey('average_score', $result[0]);
    }

    public function test_get_group_performance_calculates_per_group(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $group = $this->createGroupWithStudents(studentCount: 0);
        $exam1 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $group->exams()->attach([$exam1->id, $exam2->id], ['assigned_by' => $teacher->id]);
        $student->groups()->attach($group->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $this->createAssignmentForStudent($exam1, $student, [
            'submitted_at' => now(),
            'score' => 85.0,
        ]);

        $result = $this->service->getGroupPerformance($student);

        $this->assertCount(1, $result);
        $this->assertEquals($group->id, $result[0]['group_id']);
        $this->assertEquals(2, $result[0]['total_exams']);
        $this->assertEquals(1, $result[0]['completed_exams']);
        $this->assertEquals(85.0, $result[0]['average_score']);
        $this->assertEquals(50.0, $result[0]['completion_rate']);
    }

    public function test_get_exam_status_breakdown_counts_correctly(): void
    {
        $student = $this->createStudent();
        $exam1 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam2 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam3 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);
        $exam4 = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam1, $student, ['started_at' => null]);

        $this->createAssignmentForStudent($exam2, $student, [
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        $this->createAssignmentForStudent($exam3, $student, [
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->createAssignmentForStudent($exam4, $student, [
            'started_at' => now()->subHours(2),
            'submitted_at' => now(),
            'status' => 'graded',
        ]);

        $result = $this->service->getExamStatusBreakdown($student);

        $this->assertGreaterThanOrEqual(1, $result['not_started']);
        $this->assertGreaterThanOrEqual(1, $result['in_progress']);
        $this->assertGreaterThanOrEqual(1, $result['submitted']);
        $this->assertGreaterThanOrEqual(1, $result['graded']);

        $total = $result['not_started'] + $result['in_progress'] + $result['submitted'] + $result['graded'];
        $this->assertGreaterThanOrEqual(4, $total);
    }

    public function test_get_dashboard_stats_handles_no_active_groups(): void
    {
        $student = $this->createStudent();
        $exam = $this->createExamWithQuestions(examAttributes: ['is_active' => true], questionCount: 0);

        $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(0, $result['active_groups']);
        $this->assertIsArray($result['upcoming_exams']);
        $this->assertIsArray($result['recent_exams']);
    }
}
