<?php

namespace Tests\Unit\Services\Exam;

use Tests\TestCase;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Tests\Traits\InteractsWithTestData;

class ExamAssignmentServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    private ExamAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new ExamAssignmentService();
    }

    public function test_get_exam_assignments_returns_paginated_results(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $students = $this->createMultipleStudents(3);

        $this->createMultipleAssignments($exam, $students);

        $result = $this->service->getExamAssignments($exam, perPage: 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
    }

    public function test_get_exam_assignments_filters_by_search(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);

        $student1 = $this->createUserWithRole('student', ['name' => 'John Doe']);
        $student2 = $this->createUserWithRole('student', ['name' => 'Jane Smith']);

        $this->createAssignmentForStudent($exam, $student1);
        $this->createAssignmentForStudent($exam, $student2);

        $result = $this->service->getExamAssignments($exam, perPage: 10, search: 'John');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->student->name);
    }

    public function test_get_exam_assignments_filters_by_status(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $students = $this->createMultipleStudents(2);

        $this->createSubmittedAssignment($exam, $students[0]);
        $this->createGradedAssignment($exam, $students[1], score: 85);

        $result = $this->service->getExamAssignments($exam, perPage: 10, search: null, status: 'submitted');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('submitted', $result->items()[0]->status);
    }

    public function test_assign_exam_to_student_creates_new_assignment(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $result = $this->service->assignExamToStudent($exam, $student->id);

        $this->assertTrue($result['was_created']);
        $this->assertInstanceOf(ExamAssignment::class, $result['assignment']);
        $this->assertEquals($exam->id, $result['assignment']->exam_id);
        $this->assertEquals($student->id, $result['assignment']->student_id);
    }

    public function test_assign_exam_to_student_returns_existing_assignment(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->assignExamToStudent($exam, $student->id);

        $this->assertFalse($result['was_created']);
    }

    public function test_assign_exam_to_student_throws_for_non_student(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->assignExamToStudent($exam, $teacher->id);
    }

    public function test_assign_exam_to_students_handles_multiple_students(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $students = $this->createMultipleStudents(2);

        $studentIds = [$students[0]->id, $students[1]->id];

        $result = $this->service->assignExamToStudents($exam, $studentIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
        $this->assertEquals(2, $result['total_students']);
    }

    public function test_assign_exam_to_group_assigns_all_active_students(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $group = $this->createGroupWithStudents(studentCount: 2);

        $result = $this->service->assignExamToGroup($exam, $group->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['assigned_count']);
    }

    public function test_remove_student_assignment_deletes_assignment(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->removeStudentAssignment($exam, $student);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('exam_assignments', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_remove_student_assignment_throws_if_not_assigned(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->removeStudentAssignment($exam, $student);
    }

    public function test_get_student_assignment_with_answers_loads_relations(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $assignment = $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->getStudentAssignmentWithAnswers($exam, $student);

        $this->assertEquals($assignment->id, $result->id);
        $this->assertTrue($result->relationLoaded('answers'));
    }

    public function test_get_submitted_student_assignment_only_returns_submitted(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $student = $this->createStudent();

        $assignment = $this->createSubmittedAssignment($exam, $student);

        $result = $this->service->getSubmittedStudentAssignment($exam, $student);

        $this->assertEquals($assignment->id, $result->id);
        $this->assertNotNull($result->submitted_at);
    }

    public function test_get_assignment_form_data_includes_necessary_data(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $this->createEmptyGroup();

        $result = $this->service->getAssignmentFormData($exam);

        $this->assertArrayHasKey('exam', $result);
        $this->assertArrayHasKey('students', $result);
        $this->assertArrayHasKey('groups', $result);
        $this->assertArrayHasKey('alreadyAssigned', $result);
        $this->assertArrayHasKey('assignedStudentIds', $result);
    }
}
