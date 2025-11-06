<?php

namespace Tests\Feature\Exam;

use App\Models\User;
use App\Models\Group;
use App\Models\Exam;
use Tests\TestCase;
use App\Services\Exam\ExamAssignmentService;
use App\Services\Exam\ExamGroupService;
use Tests\Traits\InteractsWithTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamGroupAssignmentTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_assign_exam_to_group()
    {
        $teacher = User::factory()->create()->assignRole('teacher');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        $students = User::factory()->count(3)->create();
        foreach ($students as $student) {
            $student->assignRole('student');
            $student->groups()->attach($group->id, [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        $examGroupService = new ExamGroupService();
        $service = new ExamAssignmentService($examGroupService);
        $result = $service->assignExamToGroup($exam, $group->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
        $this->assertEquals(3, $result['total_students']);

        $assignments = $exam->assignments()->count();
        $this->assertEquals(3, $assignments);
    }

    public function test_handles_duplicate_assignments()
    {
        $teacher = User::factory()->create()->assignRole('teacher');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        $student = User::factory()->create()->assignRole('student');
        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $examGroupService = new ExamGroupService();
        $service = new ExamAssignmentService($examGroupService);

        $result1 = $service->assignExamToGroup($exam, $group->id);
        $this->assertEquals(1, $result1['assigned_count']);
        $this->assertEquals(0, $result1['already_assigned_count']);

        $result2 = $service->assignExamToGroup($exam, $group->id);
        $this->assertEquals(0, $result2['assigned_count']);
        $this->assertEquals(1, $result2['already_assigned_count']);
    }

    public function test_only_assigns_to_active_students()
    {
        $teacher = User::factory()->create()->assignRole('teacher');

        /** @var Exam $exam */
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        $activeStudent = User::factory()->create()->assignRole('student');
        $inactiveStudent = User::factory()->create()->assignRole('student');

        $activeStudent->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $inactiveStudent->groups()->attach($group->id, [
            'enrolled_at' => now()->subDays(30),
            'left_at' => now()->subDays(5),
            'is_active' => false,
        ]);

        $examGroupService = new ExamGroupService();
        $service = new ExamAssignmentService($examGroupService);
        $result = $service->assignExamToGroup($exam, $group->id);

        $this->assertEquals(1, $result['assigned_count']);
        $this->assertEquals(1, $result['total_students']);

        $assignments = $exam->assignments()->count();
        $this->assertEquals(1, $assignments);

        $assignedStudent = $exam->assignments()->first()->student;
        $this->assertEquals($activeStudent->id, $assignedStudent->id);
    }
}
