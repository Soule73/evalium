<?php

namespace Tests\Unit\Services\Student;

use App\Services\Student\StudentAssignmentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentAssignmentQueryServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private StudentAssignmentQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new StudentAssignmentQueryService;
    }

    public function test_get_assignments_for_student_returns_paginated_results(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createSubmittedAssignment($exam, $student);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals($exam->id, $result->items()[0]->exam_id);
    }

    public function test_get_assignments_includes_virtual_assignments_from_groups(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $group = $this->createGroupWithStudents(studentCount: 1);
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->assignExamToGroup($exam, $group, $teacher);
        $this->addStudentsToGroup($group, [$student]);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10);

        $this->assertEquals(1, $result->total());
        $assignment = $result->items()[0];
        $this->assertFalse($assignment->exists);
        $this->assertEquals($exam->id, $assignment->exam_id);
        $this->assertNull($assignment->status);
    }

    public function test_get_assignments_filters_by_status(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam1 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createGradedAssignment($exam1, $student, score: 85);
        $this->createSubmittedAssignment($exam2, $student);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10, status: 'graded');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('graded', $result->items()[0]->status);
    }

    public function test_get_assignments_filters_by_search(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam1 = $this->createExamWithQuestions($teacher, ['title' => 'Math Exam', 'is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['title' => 'Science Exam', 'is_active' => true], questionCount: 1);

        $this->createAssignmentForStudent($exam1, $student);
        $this->createAssignmentForStudent($exam2, $student);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10, status: null, search: 'Math');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Math Exam', $result->items()[0]->exam->title);
    }

    public function test_get_assignments_for_student_in_group_returns_all_group_exams(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $group = $this->createEmptyGroup();
        $exam1 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->assignExamToGroup($exam1, $group, $teacher);
        $this->assignExamToGroup($exam2, $group, $teacher);
        $this->addStudentsToGroup($group, [$student]);

        $this->createGradedAssignment($exam1, $student, score: 85);

        $result = $this->service->getAssignmentsForStudentInGroup($group, $student, perPage: 10);

        $this->assertEquals(2, $result->total());
        $this->assertTrue($result->items()[0]->exists || $result->items()[1]->exists);
        $this->assertFalse($result->items()[0]->exists && $result->items()[1]->exists);
    }

    public function test_get_student_groups_with_stats_includes_exam_counts(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $group = $this->createEmptyGroup();
        $exam1 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->assignExamToGroup($exam1, $group, $teacher);
        $this->assignExamToGroup($exam2, $group, $teacher);
        $this->addStudentsToGroup($group, [$student]);

        $this->createGradedAssignment($exam1, $student, score: 85);

        $result = $this->service->getStudentGroupsWithStats($student, perPage: 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $group = $result->items()[0];
        $this->assertEquals(2, $group->exams_count);
        $this->assertEquals(1, $group->completed_exams_count);
        $this->assertTrue($group->is_current);
    }

    public function test_get_assignments_for_student_light_returns_collection(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->getAssignmentsForStudentLight($student);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($exam->id, $result->first()->exam_id);
    }

    public function test_can_student_access_exam_returns_true_for_direct_assignment(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->canStudentAccessExam($exam, $student);

        $this->assertTrue($result);
    }

    public function test_can_student_access_exam_returns_true_for_group_access(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $group = $this->createEmptyGroup();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->assignExamToGroup($exam, $group, $teacher);
        $this->addStudentsToGroup($group, [$student]);

        $result = $this->service->canStudentAccessExam($exam, $student);

        $this->assertTrue($result);
    }

    public function test_can_student_access_exam_returns_false_for_no_access(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $result = $this->service->canStudentAccessExam($exam, $student);

        $this->assertFalse($result);
    }

    public function test_get_or_create_assignment_returns_existing_assignment(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $assignment = $this->createAssignmentForStudent($exam, $student);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $student);

        $this->assertNotNull($result);
        $this->assertTrue($result->exists);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_get_or_create_assignment_creates_virtual_assignment_for_group_access(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $group = $this->createEmptyGroup();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->assignExamToGroup($exam, $group, $teacher);
        $this->addStudentsToGroup($group, [$student]);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $student);

        $this->assertNotNull($result);
        $this->assertFalse($result->exists);
        $this->assertEquals($exam->id, $result->exam_id);
        $this->assertEquals($student->id, $result->student_id);
    }

    public function test_get_or_create_assignment_returns_null_for_no_access(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $result = $this->service->getOrCreateAssignmentForExam($exam, $student);

        $this->assertNull($result);
    }

    public function test_filter_by_status_in_progress(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam1 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createStartedAssignment($exam1, $student, [
            'started_at' => now()->subHour(),
        ]);

        $this->createAssignmentForStudent($exam2, $student);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10, status: 'in_progress');

        $this->assertEquals(1, $result->total());
        $this->assertNotNull($result->items()[0]->started_at);
        $this->assertNull($result->items()[0]->submitted_at);
    }

    public function test_filter_by_status_not_started(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $exam1 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);
        $exam2 = $this->createExamWithQuestions($teacher, ['is_active' => true], questionCount: 1);

        $this->createStartedAssignment($exam1, $student);
        $this->createAssignmentForStudent($exam2, $student);

        $result = $this->service->getAssignmentsForStudent($student, perPage: 10, status: 'not_started');

        $this->assertEquals(1, $result->total());
        $this->assertNull($result->items()[0]->started_at);
    }
}
