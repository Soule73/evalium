<?php

namespace Tests\Feature\Admin;

use App\Models\Group;
use App\Models\Level;
use App\Models\User;
use App\Services\Admin\GroupService;
use App\Services\Admin\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class GroupUserManagementTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_student_with_group_assignment()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $level = Level::where('code', 'bts_1')->first();
        $group = Group::factory()->create(['max_students' => 30, 'level_id' => $level->id]);

        $groupService = new GroupService;
        $service = new UserManagementService($groupService);

        $data = [
            'name' => 'Test Student',
            'email' => 'teststudent@example.com',
            'password' => 'password123',
            'role' => 'student',
            'group_id' => $group->id,
        ];

        $service->store($data);

        $student = User::where('email', 'teststudent@example.com')->first();

        $this->assertNotNull($student);
        $this->assertTrue($student->hasRole('student'));

        $activeGroup = $student->groups()->wherePivot('is_active', true)->first();
        $this->assertNotNull($activeGroup);
        $this->assertEquals($group->id, $activeGroup->id);
    }

    public function test_can_change_student_group()
    {
        $student = User::factory()->create()->assignRole('student');
        $oldGroup = Group::factory()->create();
        $newGroup = Group::factory()->create();

        $student->groups()->attach($oldGroup->id, [
            'enrolled_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        $groupService = new GroupService;
        $service = new UserManagementService($groupService);
        $service->changeStudentGroup($student, $newGroup->id);

        $student->refresh();

        $oldMembership = $student->groups()
            ->where('group_id', $oldGroup->id)
            ->wherePivot('is_active', false)
            ->first();
        $this->assertNotNull($oldMembership);

        $newMembership = $student->groups()
            ->where('group_id', $newGroup->id)
            ->wherePivot('is_active', true)
            ->first();
        $this->assertNotNull($newMembership);
    }

    public function test_cannot_assign_student_to_full_group()
    {
        $group = Group::factory()->create(['max_students' => 1]);

        $existingStudent = User::factory()->create()->assignRole('student');
        $existingStudent->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $groupService = new GroupService;
        $service = new UserManagementService($groupService);

        $data = [
            'name' => 'Test Student',
            'email' => 'teststudent@example.com',
            'password' => 'password123',
            'role' => 'student',
            'group_id' => $group->id,
        ];

        try {
            $service->store($data);
            $this->fail('Expected exception for full group');
        } catch (\Exception $e) {
            $this->assertStringContainsString('full', $e->getMessage());
        }
    }
}
