<?php

namespace Tests\Unit\Services\Student;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Group;
use App\Models\ExamAssignment;
use App\Services\Student\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentDashboardService $service;
    private User $student;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->service = app(StudentDashboardService::class);
        $this->student = User::factory()->create();
        $this->student->assignRole('student');
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');
    }

    public function test_get_dashboard_stats_returns_comprehensive_data(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);
        $exam3 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'status' => 'graded',
            'submitted_at' => now(),
            'score' => 85.5,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subHour(),
            'submitted_at' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam3->id,
            'student_id' => $this->student->id,
            'started_at' => null,
        ]);

        $result = $this->service->getDashboardStats($this->student);

        $this->assertGreaterThanOrEqual(3, $result['total_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['completed_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['in_progress_assignments']);
        $this->assertGreaterThanOrEqual(1, $result['not_started_assignments']);
        $this->assertIsFloat($result['average_score']);
        $this->assertGreaterThan(0, $result['completion_rate']);
    }

    public function test_get_performance_summary_calculates_correctly(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);
        $exam3 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'score' => 75.0,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'score' => 90.0,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam3->id,
            'student_id' => $this->student->id,
            'score' => 45.0,
        ]);

        $result = $this->service->getPerformanceSummary($this->student);

        $this->assertEquals(3, $result['total_graded']);
        $this->assertEquals(70.0, $result['average_score']);
        $this->assertEquals(90.0, $result['highest_score']);
        $this->assertEquals(45.0, $result['lowest_score']);
        $this->assertEquals(66.67, $result['passing_rate']);
    }

    public function test_get_performance_summary_handles_no_graded_assignments(): void
    {
        $exam = Exam::factory()->create(['is_active' => true]);
        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => null,
        ]);

        $result = $this->service->getPerformanceSummary($this->student);

        $this->assertEquals(0, $result['total_graded']);
        $this->assertNull($result['average_score']);
        $this->assertNull($result['highest_score']);
        $this->assertNull($result['lowest_score']);
        $this->assertEquals(0, $result['passing_rate']);
    }

    public function test_get_recent_activity_returns_sorted_activities(): void
    {
        $exam1 = Exam::factory()->create(['title' => 'Math Exam', 'is_active' => true]);
        $exam2 = Exam::factory()->create(['title' => 'Science Exam', 'is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'submitted_at' => now()->subDays(2),
            'score' => 80.0,
            'status' => 'graded',
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subHour(),
            'submitted_at' => null,
        ]);

        $result = $this->service->getRecentActivity($this->student, 10);

        $this->assertCount(2, $result);
        $this->assertEquals('started', $result[0]['type']);
        $this->assertEquals('Science Exam', $result[0]['exam_title']);
        $this->assertEquals('submission', $result[1]['type']);
        $this->assertEquals('Math Exam', $result[1]['exam_title']);
    }

    public function test_get_subject_performance_groups_by_subject(): void
    {
        $mathExam = Exam::factory()->create(['title' => 'Math Test', 'is_active' => true]);
        $scienceExam = Exam::factory()->create(['title' => 'Science Test', 'is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $mathExam->id,
            'student_id' => $this->student->id,
            'score' => 85.0,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $scienceExam->id,
            'student_id' => $this->student->id,
            'score' => 75.0,
        ]);

        $result = $this->service->getSubjectPerformance($this->student);

        $this->assertCount(1, $result);
        $this->assertEquals('General', $result[0]['subject']);
        $this->assertEquals(2, $result[0]['total_exams']);
    }

    public function test_get_monthly_progress_groups_by_month(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'submitted_at' => now()->subMonth(),
            'score' => 80.0,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'submitted_at' => now(),
            'score' => 90.0,
        ]);

        $result = $this->service->getMonthlyProgress($this->student, 6);

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('count', $result[0]);
        $this->assertArrayHasKey('average_score', $result[0]);
    }

    public function test_get_group_performance_calculates_per_group(): void
    {
        $group = Group::factory()->create();
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        $group->exams()->attach([$exam1->id, $exam2->id], ['assigned_by' => $this->teacher->id]);
        $this->student->groups()->attach($group->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'submitted_at' => now(),
            'score' => 85.0,
        ]);

        $result = $this->service->getGroupPerformance($this->student);

        $this->assertCount(1, $result);
        $this->assertEquals($group->id, $result[0]['group_id']);
        $this->assertEquals(2, $result[0]['total_exams']);
        $this->assertEquals(1, $result[0]['completed_exams']);
        $this->assertEquals(85.0, $result[0]['average_score']);
        $this->assertEquals(50.0, $result[0]['completion_rate']);
    }

    public function test_get_exam_status_breakdown_counts_correctly(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);
        $exam3 = Exam::factory()->create(['is_active' => true]);
        $exam4 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'started_at' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam3->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam4->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now(),
            'status' => 'graded',
        ]);

        $result = $this->service->getExamStatusBreakdown($this->student);

        $this->assertGreaterThanOrEqual(1, $result['not_started']);
        $this->assertGreaterThanOrEqual(1, $result['in_progress']);
        $this->assertGreaterThanOrEqual(1, $result['submitted']);
        $this->assertGreaterThanOrEqual(1, $result['graded']);

        $total = $result['not_started'] + $result['in_progress'] + $result['submitted'] + $result['graded'];
        $this->assertGreaterThanOrEqual(4, $total);
    }

    public function test_get_dashboard_stats_handles_no_active_groups(): void
    {
        $exam = Exam::factory()->create(['is_active' => true]);
        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->getDashboardStats($this->student);

        $this->assertEquals(0, $result['active_groups']);
        $this->assertIsArray($result['upcoming_exams']);
        $this->assertIsArray($result['recent_exams']);
    }
}
