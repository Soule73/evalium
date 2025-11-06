<?php

namespace Tests\Unit\Services\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamAssignmentService $service;
    private Exam $exam;
    private User $student;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->service = new ExamAssignmentService();

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->exam = $exam;
    }

    public function test_get_exam_assignments_returns_paginated_results(): void
    {
        ExamAssignment::factory()->count(3)->create([
            'exam_id' => $this->exam->id,
        ]);

        $result = $this->service->getExamAssignments($this->exam, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
    }

    public function test_get_exam_assignments_filters_by_search(): void
    {
        $student1 = User::factory()->create(['name' => 'John Doe']);
        $student1->assignRole('student');
        $student2 = User::factory()->create(['name' => 'Jane Smith']);
        $student2->assignRole('student');

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student1->id,
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student2->id,
        ]);

        $result = $this->service->getExamAssignments($this->exam, 10, 'John');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->student->name);
    }

    public function test_get_exam_assignments_filters_by_status(): void
    {
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'status' => 'submitted',
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'status' => 'graded',
        ]);

        $result = $this->service->getExamAssignments($this->exam, 10, null, 'submitted');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('submitted', $result->items()[0]->status);
    }

    public function test_assign_exam_to_student_creates_new_assignment(): void
    {
        $result = $this->service->assignExamToStudent($this->exam, $this->student->id);

        $this->assertTrue($result['was_created']);
        $this->assertInstanceOf(ExamAssignment::class, $result['assignment']);
        $this->assertEquals($this->exam->id, $result['assignment']->exam_id);
        $this->assertEquals($this->student->id, $result['assignment']->student_id);
    }

    public function test_assign_exam_to_student_returns_existing_assignment(): void
    {
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->assignExamToStudent($this->exam, $this->student->id);

        $this->assertFalse($result['was_created']);
    }

    public function test_assign_exam_to_student_throws_for_non_student(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->assignExamToStudent($this->exam, $this->teacher->id);
    }

    public function test_assign_exam_to_students_handles_multiple_students(): void
    {
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $studentIds = [$this->student->id, $student2->id];

        $result = $this->service->assignExamToStudents($this->exam, $studentIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
        $this->assertEquals(2, $result['total_students']);
    }

    public function test_assign_exam_to_group_assigns_all_active_students(): void
    {
        $group = Group::factory()->create();

        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $group->students()->attach([$this->student->id, $student2->id], [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $result = $this->service->assignExamToGroup($this->exam, $group->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['assigned_count']);
    }

    public function test_remove_student_assignment_deletes_assignment(): void
    {
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->removeStudentAssignment($this->exam, $this->student);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_remove_student_assignment_throws_if_not_assigned(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->removeStudentAssignment($this->exam, $this->student);
    }

    public function test_get_student_assignment_with_answers_loads_relations(): void
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
        ]);

        $result = $this->service->getStudentAssignmentWithAnswers($this->exam, $this->student);

        $this->assertEquals($assignment->id, $result->id);
        $this->assertTrue($result->relationLoaded('answers'));
    }

    public function test_get_submitted_student_assignment_only_returns_submitted(): void
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'submitted_at' => now(),
        ]);

        $result = $this->service->getSubmittedStudentAssignment($this->exam, $this->student);

        $this->assertEquals($assignment->id, $result->id);
        $this->assertNotNull($result->submitted_at);
    }

    public function test_get_assignment_form_data_includes_necessary_data(): void
    {
        $group = Group::factory()->create();

        $result = $this->service->getAssignmentFormData($this->exam);

        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('students', $result);
        $this->assertArrayHasKey('groups', $result);
        $this->assertArrayHasKey('alreadyAssigned', $result);
        $this->assertArrayHasKey('assignedStudentIds', $result);
    }
}
