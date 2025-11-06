<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\Traits\InteractsWithTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamGroupAssignmentControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createExamWithGroup(): array
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $student = $this->createStudent(['email' => 'student@test.com']);

        $group = Group::factory()->active()->create();
        $group->students()->attach($student->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true
        ]);

        return compact('teacher', 'student', 'exam', 'group');
    }

    #[Test]
    public function teacher_can_assign_exam_to_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $response = $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id]
            ]);

        $response->assertRedirect(route('exams.show', $exam));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $exam->id,
            'group_id' => $group->id
        ]);
    }

    #[Test]
    public function teacher_can_assign_exam_to_multiple_groups()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $group2 = Group::factory()->active()->create();
        $group3 = Group::factory()->active()->create();

        $response = $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id, $group2->id, $group3->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('exam_group', 3);
    }

    #[Test]
    public function teacher_cannot_assign_exam_to_invalid_group()
    {
        ['teacher' => $teacher, 'exam' => $exam] = $this->createExamWithGroup();

        $response = $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [999]
            ]);

        $response->assertSessionHasErrors('group_ids.0');
    }

    #[Test]
    public function teacher_cannot_assign_exam_twice_to_same_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id]
            ]);

        $response = $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('exam_group', 1);
    }

    #[Test]
    public function teacher_can_remove_exam_from_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $exam->groups()->attach($group->id, [
            'assigned_by' => $teacher->id,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($teacher)
            ->delete(route('exams.groups.remove', [$exam, $group->id]));

        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $exam->id,
            'group_id' => $group->id
        ]);
    }

    #[Test]
    public function teacher_cannot_remove_exam_from_non_assigned_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $response = $this->actingAs($teacher)
            ->delete(route('exams.groups.remove', [$exam, $group->id]));

        $response->assertSessionHas('error');
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_group_assignments()
    {
        ['teacher' => $teacher, 'group' => $group] = $this->createExamWithGroup();

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $otherExam), [
                'group_ids' => [$group->id]
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_group_assignment_routes()
    {
        ['student' => $student, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $response = $this->actingAs($student)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id]
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function assigning_exam_to_group_does_not_create_individual_assignments()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createExamWithGroup();

        $this->actingAs($teacher)
            ->post(route('exams.assign.groups', $exam), [
                'group_ids' => [$group->id]
            ]);

        $this->assertDatabaseCount('exam_assignments', 0);
    }
}
