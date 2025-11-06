<?php

namespace Tests\Unit\Services\Student;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Group;
use App\Models\ExamAssignment;
use App\Services\Student\StudentAssignmentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentAssignmentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentAssignmentQueryService $service;
    private User $student;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->service = new StudentAssignmentQueryService();
        $this->student = User::factory()->create();
        $this->student->assignRole('student');
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');
    }

    public function test_get_assignments_for_student_returns_paginated_results(): void
    {
        $exam = Exam::factory()->create(['is_active' => true]);
        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals($exam->id, $result->items()[0]->exam_id);
    }

    public function test_get_assignments_includes_virtual_assignments_from_groups(): void
    {
        $group = Group::factory()->create();
        $exam = Exam::factory()->create(['is_active' => true]);

        $group->exams()->attach($exam->id, ['assigned_by' => $this->teacher->id]);
        $this->student->groups()->attach($group->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10);

        $this->assertEquals(1, $result->total());
        $assignment = $result->items()[0];
        $this->assertFalse($assignment->exists);
        $this->assertEquals($exam->id, $assignment->exam_id);
        $this->assertNull($assignment->status);
    }

    public function test_get_assignments_filters_by_status(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'status' => 'graded',
            'submitted_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10, 'graded');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('graded', $result->items()[0]->status);
    }

    public function test_get_assignments_filters_by_search(): void
    {
        $exam1 = Exam::factory()->create(['title' => 'Math Exam', 'is_active' => true]);
        $exam2 = Exam::factory()->create(['title' => 'Science Exam', 'is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10, null, 'Math');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Math Exam', $result->items()[0]->exam->title);
    }

    public function test_get_assignments_for_student_in_group_returns_all_group_exams(): void
    {
        $group = Group::factory()->create();
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        $group->exams()->attach([$exam1->id, $exam2->id], ['assigned_by' => $this->teacher->id]);
        $this->student->groups()->attach($group->id, ['is_active' => true, 'enrolled_at' => now()]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'status' => 'graded',
        ]);

        $result = $this->service->getAssignmentsForStudentInGroup($group, $this->student, 10);

        $this->assertEquals(2, $result->total());
        $this->assertTrue($result->items()[0]->exists || $result->items()[1]->exists);
        $this->assertFalse($result->items()[0]->exists && $result->items()[1]->exists);
    }

    public function test_get_student_groups_with_stats_includes_exam_counts(): void
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
            'status' => 'graded',
            'submitted_at' => now(),
        ]);

        $result = $this->service->getStudentGroupsWithStats($this->student, 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $group = $result->items()[0];
        $this->assertEquals(2, $group->exams_count);
        $this->assertEquals(1, $group->completed_exams_count);
        $this->assertTrue($group->is_current);
    }

    public function test_get_assignments_for_student_light_returns_collection(): void
    {
        $exam = Exam::factory()->create(['is_active' => true]);
        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->getAssignmentsForStudentLight($this->student);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($exam->id, $result->first()->exam_id);
    }

    public function test_can_student_access_exam_returns_true_for_direct_assignment(): void
    {
        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);
        ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->canStudentAccessExam($exam, $this->student);

        $this->assertTrue($result);
    }

    public function test_can_student_access_exam_returns_true_for_group_access(): void
    {
        $group = Group::factory()->create();

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);

        $group->exams()->attach($exam->id, ['assigned_by' => $this->teacher->id]);
        $this->student->groups()->attach($group->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $result = $this->service->canStudentAccessExam($exam, $this->student);

        $this->assertTrue($result);
    }

    public function test_can_student_access_exam_returns_false_for_no_access(): void
    {
        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);

        $result = $this->service->canStudentAccessExam($exam, $this->student);

        $this->assertFalse($result);
    }

    public function test_get_or_create_assignment_returns_existing_assignment(): void
    {
        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $this->student);

        $this->assertNotNull($result);
        $this->assertTrue($result->exists);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_get_or_create_assignment_creates_virtual_assignment_for_group_access(): void
    {
        $group = Group::factory()->create();

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);

        $group->exams()->attach($exam->id, ['assigned_by' => $this->teacher->id]);
        $this->student->groups()->attach($group->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $this->student);

        $this->assertNotNull($result);
        $this->assertFalse($result->exists);
        $this->assertEquals($exam->id, $result->exam_id);
        $this->assertEquals($this->student->id, $result->student_id);
    }

    public function test_get_or_create_assignment_returns_null_for_no_access(): void
    {
        /** @var Exam $exam */
        $exam = Exam::factory()->create(['is_active' => true]);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $this->student);

        $this->assertNull($result);
    }

    public function test_filter_by_status_in_progress(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'started_at' => now()->subHour(),
            'submitted_at' => null,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'started_at' => null,
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10, 'in_progress');

        $this->assertEquals(1, $result->total());
        $this->assertNotNull($result->items()[0]->started_at);
        $this->assertNull($result->items()[0]->submitted_at);
    }

    public function test_filter_by_status_not_started(): void
    {
        $exam1 = Exam::factory()->create(['is_active' => true]);
        $exam2 = Exam::factory()->create(['is_active' => true]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam1->id,
            'student_id' => $this->student->id,
            'started_at' => now(),
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $exam2->id,
            'student_id' => $this->student->id,
            'started_at' => null,
        ]);

        $result = $this->service->getAssignmentsForStudent($this->student, 10, 'not_started');

        $this->assertEquals(1, $result->total());
        $this->assertNull($result->items()[0]->started_at);
    }
}
