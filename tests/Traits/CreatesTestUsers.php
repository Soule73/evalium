<?php

namespace Tests\Traits;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

trait CreatesTestUsers
{
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function createAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('admin');
        return $user;
    }

    protected function createTeacher(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('teacher');
        return $user;
    }

    protected function createStudent(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('student');
        return $user;
    }

    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }

    protected function createMultipleStudents(int $count, array $attributes = []): array
    {
        $students = [];

        for ($i = 0; $i < $count; $i++) {
            $students[] = $this->createStudent($attributes);
        }

        return $students;
    }

    protected function createMultipleTeachers(int $count, array $attributes = []): array
    {
        $teachers = [];

        for ($i = 0; $i < $count; $i++) {
            $teachers[] = $this->createTeacher($attributes);
        }

        return $teachers;
    }
}
