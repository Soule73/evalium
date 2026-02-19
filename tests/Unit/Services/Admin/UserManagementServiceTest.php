<?php

namespace Tests\Unit\Services\Admin;

use App\Models\User;
use App\Repositories\Admin\UserRepository;
use App\Services\Admin\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class UserManagementServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private UserManagementService $service;

    private UserRepository $queryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(UserManagementService::class);
        $this->queryService = app(UserRepository::class);
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
            'deleted_at' => null,
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
        collect()->times(15, fn () => $this->createStudent());

        $result = $this->queryService->getUserWithPagination(
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
        collect()->times(5, fn () => $this->createStudent());

        $result = $this->queryService->getUserWithPagination([], 10, $currentUser);

        $userIds = collect($result->items())->pluck('id');
        $this->assertFalse($userIds->contains($currentUser->id));
    }

    #[Test]
    public function it_can_filter_users_by_role()
    {
        $currentUser = $this->createTeacher();
        collect()->times(3, fn () => $this->createStudent());
        collect()->times(2, fn () => $this->createTeacher());

        $result = $this->queryService->getUserWithPagination(
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
        collect()->times(3, fn () => $this->createStudent([]));
        collect()->times(2, fn () => $this->createStudent(['is_active' => false]));

        $result = $this->queryService->getUserWithPagination(
            ['status' => 'active'],
            10,
            $currentUser
        );

        $this->assertEquals(3, $result->total());
        $allActive = collect($result->items())->every(fn ($u) => $u->is_active);
        $this->assertTrue($allActive);
    }

    #[Test]
    public function it_can_search_users_by_name_or_email()
    {
        $currentUser = $this->createTeacher();
        $this->createStudent(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->createStudent(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $result = $this->queryService->getUserWithPagination(
            ['search' => 'John'],
            10,
            $currentUser
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->name);
    }

    #[Test]
    public function it_can_update_user()
    {
        $user = $this->createStudent(['name' => 'Old Name']);

        $updateData = [
            'name' => 'New Name',
            'email' => 'newemail@test.com',
            'role' => 'teacher',
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
}
