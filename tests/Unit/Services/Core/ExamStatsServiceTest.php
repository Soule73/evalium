<?php

namespace Tests\Unit\Services\Core;

use Tests\TestCase;
use App\Models\Exam;
use App\Services\Core\ExamStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTestData;

class ExamStatsServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    private ExamStatsService $service;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new ExamStatsService();
        $this->exam = $this->createExamWithQuestions($this->createTeacher(), questionCount: 5);
    }

    public function test_calculate_exam_stats_with_no_assignments(): void
    {
        $stats = $this->service->calculateExamStats($this->exam);

        $this->assertEquals(0, $stats['total_assigned']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['in_progress']);
        $this->assertEquals(0, $stats['not_started']);
        $this->assertEquals(0, $stats['completion_rate']);
        $this->assertNull($stats['average_score']);
    }

    public function test_calculate_exam_stats_with_mixed_statuses(): void
    {
        $students = $this->createMultipleStudents(3);

        $this->createAssignmentForStudent($this->exam, $students[0]);

        $this->createStartedAssignment($this->exam, $students[1], [
            'started_at' => now()->subHour(),
        ]);

        $this->createSubmittedAssignment($this->exam, $students[2], [
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'score' => 40,
        ]);

        $stats = $this->service->calculateExamStats($this->exam);

        $this->assertEquals(3, $stats['total_assigned']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['not_started']);
        $this->assertEquals(33.33, $stats['completion_rate']);
        $this->assertEquals(40, $stats['average_score']);
    }

    public function test_calculate_group_stats(): void
    {
        $group = $this->createGroupWithStudents(studentCount: 3);

        $this->createGradedAssignment($this->exam, $group->students[0], score: 45);

        $this->createStartedAssignment($this->exam, $group->students[1]);

        $group->refresh();
        $stats = $this->service->calculateGroupStats($this->exam, $group);

        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['not_started']);
        $this->assertEquals(45, $stats['average_score']);
    }
    public function test_calculate_student_progress(): void
    {
        $student = $this->createStudent();
        $teacher = $this->exam->teacher;

        $exam1 = $this->createExamWithQuestions($teacher, questionCount: 10);
        $exam2 = $this->createExamWithQuestions($teacher, questionCount: 10);

        $this->createGradedAssignment($exam1, $student, score: 40);
        $this->createAssignmentForStudent($exam2, $student);

        $progress = $this->service->calculateStudentProgress($student);

        $this->assertEquals(2, $progress['total_exams']);
        $this->assertEquals(1, $progress['completed_exams']);
        $this->assertEquals(1, $progress['pending_exams']);
        $this->assertGreaterThan(0, $progress['average_score']);
    }

    public function test_calculate_completion_rate(): void
    {
        $assignments = collect([
            (object)['status' => 'submitted'],
            (object)['status' => 'graded'],
            (object)['status' => null],
        ]);

        $rate = $this->service->calculateCompletionRate($assignments, 4);

        $this->assertEquals(50.0, $rate);
    }

    public function test_calculate_completion_rate_with_zero_total(): void
    {
        $assignments = collect([]);

        $rate = $this->service->calculateCompletionRate($assignments, 0);

        $this->assertEquals(0.0, $rate);
    }

    public function test_calculate_average_score(): void
    {
        $assignments = collect([
            (object)['score' => 80],
            (object)['score' => 90],
            (object)['score' => null],
        ]);

        $average = $this->service->calculateAverageScore($assignments);

        $this->assertEquals(85.0, $average);
    }

    public function test_calculate_average_score_with_no_scores(): void
    {
        $assignments = collect([
            (object)['score' => null],
            (object)['score' => null],
        ]);

        $average = $this->service->calculateAverageScore($assignments);

        $this->assertNull($average);
    }

    public function test_count_by_status(): void
    {
        $assignments = collect([
            (object)['started_at' => null, 'submitted_at' => null, 'status' => null],
            (object)['started_at' => now(), 'submitted_at' => null, 'status' => null],
            (object)['started_at' => now(), 'submitted_at' => now(), 'status' => 'submitted'],
            (object)['started_at' => now(), 'submitted_at' => now(), 'status' => 'graded'],
        ]);

        $counts = $this->service->countByStatus($assignments);

        $this->assertEquals(1, $counts['not_started']);
        $this->assertEquals(1, $counts['in_progress']);
        $this->assertEquals(1, $counts['submitted']);
        $this->assertEquals(1, $counts['graded']);
    }

    public function test_calculate_exam_stats_with_groups(): void
    {
        $group = $this->createGroupWithStudents(studentCount: 5);

        $group->load('activeStudents');
        $assignedGroups = collect([$group]);

        $this->createSubmittedAssignment($this->exam, $group->students[0]);
        $this->createSubmittedAssignment($this->exam, $group->students[1]);

        $stats = $this->service->calculateExamStatsWithGroups($this->exam, $assignedGroups);

        $this->assertEquals(5, $stats['total_assigned']);
        $this->assertEquals(2, $stats['completed']);
        $this->assertGreaterThanOrEqual(0, $stats['not_started']);
    }
}
