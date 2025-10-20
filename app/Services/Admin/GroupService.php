<?php

namespace App\Services\Admin;

use App\Models\Group;
use App\Models\User;
use App\Models\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GroupService
{
    public function getGroupsWithPagination(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Group::with(['activeStudents', 'level'])
            ->withCount('activeStudents');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('academic_year', 'like', "%{$search}%")
                    ->orWhereHas('level', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['level_id'])) {
            $query->where('level_id', $filters['level_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('academic_year', 'desc')
            ->orderBy('level_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createGroup(array $data): Group
    {
        return DB::transaction(function () use ($data) {
            return Group::create([
                'level_id' => $data['level_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'max_students' => $data['max_students'],
                'academic_year' => $data['academic_year'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updateGroup(Group $group, array $data): Group
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update([
                'level_id' => $data['level_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'max_students' => $data['max_students'],
                'academic_year' => $data['academic_year'],
                'is_active' => $data['is_active'] ?? $group->is_active,
            ]);

            return $group->fresh();
        });
    }

    public function deleteGroup(Group $group): bool
    {
        return DB::transaction(function () use ($group) {
            $group->students()->detach();
            return $group->delete();
        });
    }

    public function assignStudentsToGroup(Group $group, array $studentIds): array
    {
        return DB::transaction(function () use ($group, $studentIds) {
            $assignedCount = 0;
            $alreadyAssignedCount = 0;
            $errors = [];

            foreach ($studentIds as $studentId) {
                try {
                    $result = $this->assignStudentToGroup($group, $studentId);
                    if ($result['assigned']) {
                        $assignedCount++;
                    } else {
                        $alreadyAssignedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            return [
                'assigned_count' => $assignedCount,
                'already_assigned_count' => $alreadyAssignedCount,
                'errors' => $errors,
            ];
        });
    }

    public function assignStudentToGroup(Group $group, int $studentId): array
    {
        $student = User::findOrFail($studentId);

        if (!$student->hasRole('student')) {
            throw new \InvalidArgumentException("L'utilisateur n'est pas un étudiant.");
        }

        if (!$group->hasAvailableSlots()) {
            throw new \Exception("Le groupe '{$group->name}' est complet.");
        }

        $existingActiveAssignment = $student->groups()
            ->wherePivot('group_id', $group->id)
            ->wherePivot('is_active', true)
            ->exists();

        if ($existingActiveAssignment) {
            return ['assigned' => false, 'message' => 'Déjà assigné'];
        }

        $this->deactivateCurrentGroupAssignments($student);

        $existingAssignment = $student->groups()
            ->where('group_id', $group->id)
            ->first();

        if ($existingAssignment) {
            $student->groups()->updateExistingPivot($group->id, [
                'is_active' => true,
                'enrolled_at' => now(),
                'left_at' => null,
            ]);
        } else {
            $student->groups()->attach($group->id, [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        return ['assigned' => true, 'message' => 'Assigné avec succès'];
    }

    public function removeStudentFromGroup(Group $group, User $student): bool
    {
        return DB::transaction(function () use ($group, $student) {
            $assignment = $student->groups()
                ->where('group_id', $group->id)
                ->wherePivot('is_active', true)
                ->first();

            if (!$assignment) {
                throw new \InvalidArgumentException("L'étudiant n'est pas assigné à ce groupe.");
            }

            $student->groups()->updateExistingPivot($group->id, [
                'is_active' => false,
                'left_at' => now(),
            ]);

            return true;
        });
    }

    public function getAvailableStudents(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role('student')
            ->whereDoesntHave('groups', function ($query) {
                $query->where('group_student.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function getFormData(): array
    {
        return [
            'levels' => Level::options(),
            'available_students' => $this->getAvailableStudents(),
        ];
    }

    private function deactivateCurrentGroupAssignments(User $student): void
    {
        $activeGroups = $student->groups()->wherePivot('is_active', true)->get();

        foreach ($activeGroups as $activeGroup) {
            $student->groups()->updateExistingPivot($activeGroup->id, [
                'is_active' => false,
                'left_at' => now(),
            ]);
        }
    }

    public function bulkActivate(array $groupIds): array
    {
        return DB::transaction(function () use ($groupIds) {
            $activatedCount = 0;
            $alreadyActiveCount = 0;

            $groups = Group::whereIn('id', $groupIds)->get();

            foreach ($groups as $group) {
                if ($group->is_active) {
                    $alreadyActiveCount++;
                } else {
                    $group->update(['is_active' => true]);
                    $activatedCount++;
                }
            }

            return [
                'activated_count' => $activatedCount,
                'already_active_count' => $alreadyActiveCount,
            ];
        });
    }

    public function bulkDeactivate(array $groupIds): array
    {
        return DB::transaction(function () use ($groupIds) {
            $deactivatedCount = 0;
            $alreadyInactiveCount = 0;

            $groups = Group::whereIn('id', $groupIds)->get();

            foreach ($groups as $group) {
                if (!$group->is_active) {
                    $alreadyInactiveCount++;
                } else {
                    $group->update(['is_active' => false]);
                    $deactivatedCount++;
                }
            }

            return [
                'deactivated_count' => $deactivatedCount,
                'already_inactive_count' => $alreadyInactiveCount,
            ];
        });
    }

    public function bulkRemoveStudentsFromGroup(Group $group, array $studentIds): array
    {
        return DB::transaction(function () use ($group, $studentIds) {
            $removedCount = 0;
            $notInGroupCount = 0;

            foreach ($studentIds as $studentId) {
                $student = User::find($studentId);

                if (!$student) {
                    $notInGroupCount++;
                    continue;
                }

                $assignment = $student->groups()
                    ->where('group_id', $group->id)
                    ->wherePivot('is_active', true)
                    ->first();

                if (!$assignment) {
                    $notInGroupCount++;
                    continue;
                }

                $student->groups()->updateExistingPivot($group->id, [
                    'is_active' => false,
                    'left_at' => now(),
                ]);

                $removedCount++;
            }

            return [
                'removed_count' => $removedCount,
                'not_in_group_count' => $notInGroupCount,
            ];
        });
    }
}
