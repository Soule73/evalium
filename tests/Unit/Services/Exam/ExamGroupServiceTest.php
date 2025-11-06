<?php

namespace Tests\Unit\Services\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Services\Exam\ExamGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class ExamGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamGroupService $service;
    private Exam $exam;
    private Group $group;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->service = new ExamGroupService();

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->exam = $exam;
        $this->group = Group::factory()->create();

        Auth::login($this->teacher);
    }

    public function test_assign_exam_to_groups_creates_new_assignments(): void
    {
        $group2 = Group::factory()->create();

        $result = $this->service->assignExamToGroups(
            $this->exam,
            [$this->group->id, $group2->id],
            $this->teacher->id
        );

        $this->assertEquals(2, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);

        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
        ]);
    }

    public function test_assign_exam_to_groups_detects_duplicates(): void
    {
        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $group2 = Group::factory()->create();

        $result = $this->service->assignExamToGroups(
            $this->exam,
            [$this->group->id, $group2->id],
            $this->teacher->id
        );

        $this->assertEquals(1, $result['assigned_count']);
        $this->assertEquals(1, $result['already_assigned_count']);
    }

    public function test_assign_exam_to_groups_validates_group_existence(): void
    {
        $result = $this->service->assignExamToGroups(
            $this->exam,
            [9999],
            $this->teacher->id
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
            'assigned_by' => $this->teacher->id,
        ]);
    }

    public function test_remove_exam_from_group_deletes_relationship(): void
    {
        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

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
        $group2 = Group::factory()->create();

        $this->exam->groups()->attach([
            $this->group->id => ['assigned_by' => $this->teacher->id, 'assigned_at' => now()],
            $group2->id => ['assigned_by' => $this->teacher->id, 'assigned_at' => now()],
        ]);

        $count = $this->service->removeExamFromGroups($this->exam, [$this->group->id, $group2->id]);

        $this->assertEquals(2, $count);
        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
        ]);
    }

    public function test_get_groups_for_exam_loads_relationships(): void
    {
        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $groups = $this->service->getGroupsForExam($this->exam);

        $this->assertEquals(1, $groups->count());
        $this->assertTrue($groups->first()->relationLoaded('level'));
        $this->assertTrue($groups->first()->relationLoaded('activeStudents'));
        $this->assertArrayHasKey('active_students_count', $groups->first()->toArray());
    }

    public function test_get_exams_for_group_loads_relationships(): void
    {
        $this->group->exams()->attach($this->exam->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $exams = $this->service->getExamsForGroup($this->group);

        $this->assertEquals(1, $exams->count());
        $this->assertTrue($exams->first()->relationLoaded('teacher'));
        $this->assertArrayHasKey('questions_count', $exams->first()->toArray());
    }

    public function test_is_exam_assigned_to_group_returns_true_when_assigned(): void
    {
        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

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
        $group2 = Group::factory()->create(['is_active' => true]);
        $group3 = Group::factory()->create(['is_active' => true]);

        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $availableGroups = $this->service->getAvailableGroupsForExam($this->exam);

        $this->assertGreaterThanOrEqual(2, $availableGroups->count());
        $this->assertFalse($availableGroups->contains('id', $this->group->id));
    }

    public function test_get_available_groups_for_exam_only_includes_active_groups(): void
    {
        $inactiveGroup = Group::factory()->create(['is_active' => false]);

        $availableGroups = $this->service->getAvailableGroupsForExam($this->exam);

        $this->assertFalse($availableGroups->contains('id', $inactiveGroup->id));
    }

    public function test_get_total_students_for_exam_calculates_correctly(): void
    {
        $student1 = User::factory()->create();
        $student1->assignRole('student');
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $this->group->students()->attach([
            $student1->id => ['is_active' => true, 'enrolled_at' => now()],
            $student2->id => ['is_active' => true, 'enrolled_at' => now()],
        ]);

        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $total = $this->service->getTotalStudentsForExam($this->exam);

        $this->assertEquals(2, $total);
    }

    public function test_get_total_students_for_exam_returns_zero_when_no_groups(): void
    {
        $total = $this->service->getTotalStudentsForExam($this->exam);

        $this->assertEquals(0, $total);
    }
}
