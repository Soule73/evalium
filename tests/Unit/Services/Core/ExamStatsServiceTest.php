<?php

namespace Tests\Unit\Services\Core;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Question;
use App\Models\ExamAssignment;
use App\Services\Core\ExamStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamStatsService $service;
    private User $teacher;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->service = new ExamStatsService();
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        $this->exam = $exam;

        Question::factory()->count(5)->create([
            'exam_id' => $this->exam->id,
            'points' => 10,
        ]);
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
        $student1 = User::factory()->create();
        $student1->assignRole('student');
        $student2 = User::factory()->create();
        $student2->assignRole('student');
        $student3 = User::factory()->create();
        $student3->assignRole('student');

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student1->id,
            'started_at' => null,
            'submitted_at' => null,
            'status' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student2->id,
            'started_at' => now()->subHour(),
            'submitted_at' => null,
            'status' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student3->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'status' => 'submitted',
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
        $group = Group::factory()->create();
        $students = User::factory()->count(3)->create();

        foreach ($students as $student) {
            $student->assignRole('student');
            $group->students()->attach($student->id, [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $students[0]->id,
            'started_at' => now(),
            'submitted_at' => now(),
            'status' => 'graded',
            'score' => 45,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $students[1]->id,
            'started_at' => now(),
            'submitted_at' => null,
        ]);

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
        $student = User::factory()->create();
        $student->assignRole('student');

        $exam1 = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
        Question::factory()->count(10)->create(['exam_id' => $exam1->id, 'points' => 5]);

        $exam2 = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
        Question::factory()->count(10)->create(['exam_id' => $exam2->id, 'points' => 5]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $student->id,
            'status' => 'graded',
            'score' => 40,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $student->id,
            'started_at' => null,
            'submitted_at' => null,
        ]);

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
        $group = Group::factory()->create();
        $students = User::factory()->count(5)->create();

        foreach ($students as $student) {
            $student->assignRole('student');
            $group->students()->attach($student->id, [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        $group->load('activeStudents');
        $assignedGroups = collect([$group]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $students[0]->id,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $students[1]->id,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $stats = $this->service->calculateExamStatsWithGroups($this->exam, $assignedGroups);

        $this->assertEquals(5, $stats['total_assigned']);
        $this->assertEquals(2, $stats['completed']);
        $this->assertGreaterThanOrEqual(0, $stats['not_started']);
    }
}
