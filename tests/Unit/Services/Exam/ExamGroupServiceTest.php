<?php

namespace Tests\Unit\Services\Exam;

use App\Models\Exam;
use App\Models\Group;
use App\Services\Exam\ExamGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ExamGroupServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ExamGroupService $service;

    private Exam $exam;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        $this->service = new ExamGroupService;

        $teacher = $this->createTeacher();
        $this->exam = $this->createExamWithQuestions($teacher, questionCount: 1);
        $this->group = $this->createEmptyGroup();

        Auth::login($teacher);
    }

    public function test_assign_exam_to_groups_creates_new_assignments(): void
    {
        $group2 = $this->createEmptyGroup();

        $result = $this->service->assignExamToGroups(
            $this->exam,
            [$this->group->id, $group2->id],
            $this->exam->teacher->id
        );

        $this->assertEquals(2, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);

        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->exam->teacher->id,
        ]);
    }

    public function test_assign_exam_to_groups_detects_duplicates(): void
    {
        $this->assignExamToGroup($this->exam, $this->group);

        $group2 = $this->createEmptyGroup();

        $result = $this->service->assignExamToGroups(
            $this->exam,
            [$this->group->id, $group2->id],
            $this->exam->teacher->id
        );

        $this->assertEquals(1, $result['assigned_count']);
        $this->assertEquals(1, $result['already_assigned_count']);
    }

    public function test_assign_exam_to_groups_validates_group_existence(): void
    {
        $result = $this->service->assignExamToGroups(
            $this->exam,
            [9999],
            $this->exam->teacher->id
        );

        $this->assertEquals(0, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
    }

    public function test_assign_exam_to_groups_uses_authenticated_user_by_default(): void
    {
        $result = $this->service->assignExamToGroups($this->exam, [$this->group->id]);

        $this->assertEquals(1, $result['assigned_count']);

        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->exam->teacher->id,
        ]);
    }

    public function test_remove_exam_from_group_deletes_relationship(): void
    {
        $this->assignExamToGroup($this->exam, $this->group);

        $result = $this->service->removeExamFromGroup($this->exam, $this->group);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
        ]);
    }

    public function test_remove_exam_from_group_returns_false_if_not_assigned(): void
    {
        $result = $this->service->removeExamFromGroup($this->exam, $this->group);

        $this->assertFalse($result);
    }

    public function test_remove_exam_from_groups_deletes_multiple_relationships(): void
    {
        $group2 = $this->createEmptyGroup();

        $this->assignExamToGroup($this->exam, $this->group);
        $this->assignExamToGroup($this->exam, $group2);

        $count = $this->service->removeExamFromGroups($this->exam, [$this->group->id, $group2->id]);

        $this->assertEquals(2, $count);
        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
        ]);
    }

    public function test_get_groups_for_exam_loads_relationships(): void
    {
        $this->assignExamToGroup($this->exam, $this->group);

        $groups = $this->service->getGroupsForExam($this->exam);

        $this->assertEquals(1, $groups->count());
        $this->assertTrue($groups->first()->relationLoaded('level'));
        $this->assertTrue($groups->first()->relationLoaded('activeStudents'));
        $this->assertArrayHasKey('active_students_count', $groups->first()->toArray());
    }

    public function test_get_exams_for_group_loads_relationships(): void
    {
        $this->assignExamToGroup($this->exam, $this->group);

        $exams = $this->service->getExamsForGroup($this->group);

        $this->assertEquals(1, $exams->count());
        $this->assertTrue($exams->first()->relationLoaded('teacher'));
        $this->assertArrayHasKey('questions_count', $exams->first()->toArray());
    }

    public function test_is_exam_assigned_to_group_returns_true_when_assigned(): void
    {
        $this->assignExamToGroup($this->exam, $this->group);

        $result = $this->service->isExamAssignedToGroup($this->exam, $this->group);

        $this->assertTrue($result);
    }

    public function test_is_exam_assigned_to_group_returns_false_when_not_assigned(): void
    {
        $result = $this->service->isExamAssignedToGroup($this->exam, $this->group);

        $this->assertFalse($result);
    }

    public function test_get_available_groups_for_exam_excludes_assigned_groups(): void
    {
        $group2 = $this->createEmptyGroup(['is_active' => true]);
        $group3 = $this->createEmptyGroup(['is_active' => true]);

        $this->assignExamToGroup($this->exam, $this->group);

        $availableGroups = $this->service->getAvailableGroupsForExam($this->exam);

        $this->assertGreaterThanOrEqual(2, $availableGroups->count());
        $this->assertFalse($availableGroups->contains('id', $this->group->id));
    }

    public function test_get_available_groups_for_exam_only_includes_active_groups(): void
    {
        $inactiveGroup = $this->createEmptyGroup(['is_active' => false]);

        $availableGroups = $this->service->getAvailableGroupsForExam($this->exam);

        $this->assertFalse($availableGroups->contains('id', $inactiveGroup->id));
    }

    public function test_get_total_students_for_exam_calculates_correctly(): void
    {
        $students = $this->createMultipleStudents(2);

        foreach ($students as $student) {
            $this->group->students()->attach($student->id, [
                'is_active' => true,
                'enrolled_at' => now(),
            ]);
        }

        $this->assignExamToGroup($this->exam, $this->group);

        $total = $this->service->getTotalStudentsForExam($this->exam);

        $this->assertEquals(2, $total);
    }

    public function test_get_total_students_for_exam_returns_zero_when_no_groups(): void
    {
        $total = $this->service->getTotalStudentsForExam($this->exam);

        $this->assertEquals(0, $total);
    }
}
