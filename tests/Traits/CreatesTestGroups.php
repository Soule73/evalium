<?php

namespace Tests\Traits;

use App\Models\Group;
use App\Models\Level;
use App\Models\User;

trait CreatesTestGroups
{
    protected function createGroupWithStudents(int $studentCount = 2, array $groupAttributes = []): Group
    {
        $level = Level::factory()->create();

        $group = Group::factory()->create(array_merge([
            'level_id' => $level->id,
        ], $groupAttributes));

        $students = $this->createMultipleStudents($studentCount);

        foreach ($students as $student) {
            $group->students()->attach($student->id);
        }

        return $group->load('students', 'level');
    }

    protected function createEmptyGroup(array $attributes = []): Group
    {
        $level = Level::factory()->create();

        return Group::factory()->create(array_merge([
            'level_id' => $level->id,
        ], $attributes));
    }

    protected function addStudentsToGroup(Group $group, array $students): void
    {
        foreach ($students as $student) {
            $group->students()->attach($student->id);
        }
    }

    protected function createLevel(array $attributes = []): Level
    {
        return Level::factory()->create($attributes);
    }

    protected function createMultipleGroups(int $count, int $studentsPerGroup = 0): array
    {
        $groups = [];

        for ($i = 0; $i < $count; $i++) {
            if ($studentsPerGroup > 0) {
                $groups[] = $this->createGroupWithStudents($studentsPerGroup);
            } else {
                $groups[] = $this->createEmptyGroup();
            }
        }

        return $groups;
    }
}
