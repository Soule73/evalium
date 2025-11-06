<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Notification;
use App\Services\Admin\UserManagementService;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTestData;

class UserManagementServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestData;

    private UserManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(UserManagementService::class);
    }

    #[Test]
    public function it_can_get_active_groups_with_levels()
    {
        Cache::flush();

        $level = $this->createLevel(['name' => 'Level 1']);
        $activeGroup = $this->createGroupWithStudents(0, ['is_active' => true, 'level_id' => $level->id]);
        $this->createGroupWithStudents(0, ['is_active' => false, 'level_id' => $level->id]);

        $groups = $this->service->getActiveGroupsWithLevels();

        $this->assertCount(1, $groups);
        $this->assertEquals($activeGroup->id, $groups->first()->id);
        $this->assertTrue($groups->first()->relationLoaded('level'));
        $this->assertEquals('Level 1', $groups->first()->level->name);
    }

    #[Test]
    public function it_caches_active_groups_with_levels()
    {
        Cache::flush();

        $level = $this->createLevel();
        $this->createGroupWithStudents(0, ['is_active' => true, 'level_id' => $level->id]);

        $this->service->getActiveGroupsWithLevels();

        $this->assertTrue(Cache::has('groups_active_with_levels'));
    }

    #[Test]
    public function it_can_restore_a_soft_deleted_user()
    {
        $user = $this->createStudent(['name' => 'Deleted User']);
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $restoredUser = $this->service->restoreUser($user->id);

        $this->assertInstanceOf(User::class, $restoredUser);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);
        $this->assertEquals('Deleted User', $restoredUser->name);
    }

    #[Test]
    public function it_throws_exception_when_restoring_nonexistent_user()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->restoreUser(99999);
    }

    #[Test]
    public function it_can_force_delete_a_user()
    {
        $user = $this->createStudent(['name' => 'To Be Deleted']);
        $userId = $user->id;

        $result = $this->service->forceDeleteUser($userId);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    #[Test]
    public function it_can_force_delete_a_soft_deleted_user()
    {
        $user = $this->createStudent();
        $userId = $user->id;
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);

        $result = $this->service->forceDeleteUser($userId);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    #[Test]
    public function it_throws_exception_when_force_deleting_nonexistent_user()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->forceDeleteUser(99999);
    }

    #[Test]
    public function it_can_get_paginated_users()
    {
        $currentUser = $this->createTeacher();
        collect()->times(15, fn() => $this->createStudent());

        $result = $this->service->getUserWithPagination(
            ['per_page' => 10],
            10,
            $currentUser
        );

        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
    }

    #[Test]
    public function it_excludes_current_user_from_pagination()
    {
        $currentUser = $this->createTeacher();
        collect()->times(5, fn() => $this->createStudent());

        $result = $this->service->getUserWithPagination([], 10, $currentUser);

        $userIds = collect($result->items())->pluck('id');
        $this->assertFalse($userIds->contains($currentUser->id));
    }

    #[Test]
    public function it_can_filter_users_by_role()
    {
        $currentUser = $this->createTeacher();
        collect()->times(3, fn() => $this->createStudent());
        collect()->times(2, fn() => $this->createTeacher());

        $result = $this->service->getUserWithPagination(
            ['role' => 'student'],
            10,
            $currentUser
        );

        $this->assertEquals(3, $result->total());
    }

    #[Test]
    public function it_can_filter_users_by_status()
    {
        $currentUser = $this->createTeacher();
        collect()->times(3, fn() => $this->createStudent(['is_active' => true]));
        collect()->times(2, fn() => $this->createStudent(['is_active' => false]));

        $result = $this->service->getUserWithPagination(
            ['status' => 'active'],
            10,
            $currentUser
        );

        $this->assertEquals(3, $result->total());
        $allActive = collect($result->items())->every(fn($u) => $u->is_active);
        $this->assertTrue($allActive);
    }

    #[Test]
    public function it_can_search_users_by_name_or_email()
    {
        $currentUser = $this->createTeacher();
        $this->createStudent(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->createStudent(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $result = $this->service->getUserWithPagination(
            ['search' => 'John'],
            10,
            $currentUser
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->name);
    }

    #[Test]
    public function it_can_create_user_with_notification()
    {
        Notification::fake();

        $level = $this->createLevel();
        $group = $this->createGroupWithStudents(0, ['level_id' => $level->id]);

        $userData = [
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'role' => 'student',
            'group_id' => $group->id
        ];

        $user = $this->service->store($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test Student', $user->name);
        $this->assertTrue($user->hasRole('student'));
        $this->assertNotNull($user->password);

        Notification::assertSentTo($user, UserCredentialsNotification::class);
    }

    #[Test]
    public function it_can_update_user()
    {
        $user = $this->createStudent(['name' => 'Old Name']);

        $updateData = [
            'name' => 'New Name',
            'email' => 'newemail@test.com',
            'role' => 'teacher'
        ];

        $this->service->update($user, $updateData);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('newemail@test.com', $user->email);
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertFalse($user->hasRole('student'));
    }

    #[Test]
    public function it_can_soft_delete_user()
    {
        $user = $this->createStudent();

        $this->service->delete($user);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function it_can_toggle_user_status()
    {
        $user = $this->createStudent(['is_active' => true]);

        $this->service->toggleStatus($user);
        $this->assertFalse($user->is_active);

        $this->service->toggleStatus($user);
        $this->assertTrue($user->is_active);
    }

    #[Test]
    public function it_can_change_student_group()
    {
        $level = $this->createLevel();
        $newGroup = $this->createGroupWithStudents(0, ['level_id' => $level->id]);

        $student = $this->createStudent();

        $this->service->changeStudentGroup($student, $newGroup->id);

        $this->assertDatabaseHas('group_student', [
            'student_id' => $student->id,
            'group_id' => $newGroup->id,
            'is_active' => true
        ]);
    }

    #[Test]
    public function it_throws_exception_when_changing_group_for_non_student()
    {
        $user = $this->createTeacher();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("User must be a student.");

        $this->service->changeStudentGroup($user, 1);
    }
}
