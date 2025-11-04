<?php

namespace App\Services\Admin;

use App\Models\Group;
use App\Models\User;
use App\Models\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Group Service - Handle group management and student assignments
 * 
 * Single Responsibility: Manage groups, student enrollments, and capacity control
 * Uses bulk operations for performance optimization
 */
class GroupService
{
    /**
     * Get paginated groups with filters
     *
     * @param array $filters Filter criteria (search, level_id, is_active)
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    /**
     * Get paginated groups with filters
     *
     * @param array $filters Filter criteria (search, level_id, is_active)
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getGroupsWithPagination(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Group::with('level')
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

    /**
     * Create a new group
     *
     * @param array $data Group data
     * @return Group
     */
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

    /**
     * Update an existing group
     *
     * @param Group $group Group to update
     * @param array $data Updated data
     * @return Group
     */
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

    /**
     * Delete a group and detach all students
     *
     * @param Group $group Group to delete
     * @return bool
     */
    public function deleteGroup(Group $group): bool
    {
        return DB::transaction(function () use ($group) {
            $group->students()->detach();
            return $group->delete();
        });
    }

    /**
     * Assign multiple students to a group with validation and capacity control
     * Optimized with bulk operations and decomposed methods
     *
     * @param Group $group Target group
     * @param array $studentIds Student IDs to assign
     * @return array Result with assigned count, already assigned count, and errors
     */
    public function assignStudentsToGroup(Group $group, array $studentIds): array
    {
        return DB::transaction(function () use ($group, $studentIds) {
            $validation = $this->validateStudentsForAssignment($studentIds);
            if (empty($validation['validStudentIds'])) {
                return $this->buildEmptyResult($validation['errors']);
            }

            $filtered = $this->filterAlreadyAssignedStudents($group, $validation['validStudentIds']);
            if (empty($filtered['studentsToAssign'])) {
                return $this->buildResult(0, $filtered['alreadyAssignedCount'], $validation['errors']);
            }

            $limited = $this->limitByGroupCapacity(
                $group,
                $filtered['studentsToAssign'],
                $validation['validStudentIds'],
                $filtered['alreadyAssignedCount'],
                $validation['errors']
            );

            if (empty($limited['studentsToAssign'])) {
                return $this->buildResult(0, $filtered['alreadyAssignedCount'], $limited['errors']);
            }

            $this->performBulkAssignments($group, $limited['studentsToAssign']);

            return $this->buildResult(
                count($limited['studentsToAssign']),
                $filtered['alreadyAssignedCount'],
                $limited['errors']
            );
        });
    }

    /**
     * Validate that users exist and have student role
     *
     * @param array $studentIds Student IDs to validate
     * @return array Valid student IDs and errors
     */
    private function validateStudentsForAssignment(array $studentIds): array
    {
        $students = User::with('roles')->whereIn('id', $studentIds)->get()->keyBy('id');
        $errors = [];
        $validStudentIds = [];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);

            if (!$student) {
                $errors[] = "User #{$studentId} not found";
                continue;
            }

            if (!$student->hasRole('student')) {
                $errors[] = "User #{$studentId} is not a student";
                continue;
            }

            $validStudentIds[] = $studentId;
        }

        return compact('validStudentIds', 'errors');
    }

    /**
     * Filter students already assigned to the group
     *
     * @param Group $group Target group
     * @param array $validStudentIds Valid student IDs
     * @return array Students to assign and already assigned count
     */
    /**
     * Filter students already assigned to the group
     *
     * @param Group $group Target group
     * @param array $validStudentIds Valid student IDs
     * @return array Students to assign and already assigned count
     */
    private function filterAlreadyAssignedStudents(Group $group, array $validStudentIds): array
    {
        $existingActiveAssignments = DB::table('group_student')
            ->where('group_id', $group->id)
            ->whereIn('student_id', $validStudentIds)
            ->where('is_active', true)
            ->pluck('student_id')
            ->toArray();

        return [
            'alreadyAssignedCount' => count($existingActiveAssignments),
            'studentsToAssign' => array_diff($validStudentIds, $existingActiveAssignments),
        ];
    }

    /**
     * Limit assignments based on group capacity
     *
     * @param Group $group Target group
     * @param array $studentsToAssign Students to assign
     * @param array $validStudentIds All valid student IDs
     * @param int $alreadyAssignedCount Count of already assigned students
     * @param array $errors Current errors
     * @return array Limited students to assign and updated errors
     */
    private function limitByGroupCapacity(
        Group $group,
        array $studentsToAssign,
        array $validStudentIds,
        int $alreadyAssignedCount,
        array $errors
    ): array {
        $availableSlots = $group->max_students - $group->activeStudents()->count();

        if ($availableSlots < count($studentsToAssign)) {
            $studentsToAssign = array_slice($studentsToAssign, 0, $availableSlots);
            $rejectedCount = count($validStudentIds) - $alreadyAssignedCount - count($studentsToAssign);

            if ($rejectedCount > 0) {
                $errors[] = "Group '{$group->name}' has only {$availableSlots} available slot(s), {$rejectedCount} student(s) not assigned";
            }
        }

        return compact('studentsToAssign', 'errors');
    }

    /**
     * Perform bulk student assignments (deactivate old, reactivate/create new)
     *
     * @param Group $group Target group
     * @param array $studentsToAssign Students to assign
     * @return void
     */
    /**
     * Perform bulk student assignments (deactivate old, reactivate/create new)
     *
     * @param Group $group Target group
     * @param array $studentsToAssign Students to assign
     * @return void
     */
    private function performBulkAssignments(Group $group, array $studentsToAssign): void
    {
        DB::table('group_student')
            ->whereIn('student_id', $studentsToAssign)
            ->where('is_active', true)
            ->update(['is_active' => false, 'left_at' => now()]);

        $oldInactiveAssignments = DB::table('group_student')
            ->where('group_id', $group->id)
            ->whereIn('student_id', $studentsToAssign)
            ->where('is_active', false)
            ->pluck('student_id')
            ->toArray();

        if (!empty($oldInactiveAssignments)) {
            DB::table('group_student')
                ->where('group_id', $group->id)
                ->whereIn('student_id', $oldInactiveAssignments)
                ->where('is_active', false)
                ->update(['is_active' => true, 'enrolled_at' => now(), 'left_at' => null]);
        }

        $newStudents = array_diff($studentsToAssign, $oldInactiveAssignments);
        if (!empty($newStudents)) {
            $insertData = array_map(fn($studentId) => [
                'group_id' => $group->id,
                'student_id' => $studentId,
                'enrolled_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $newStudents);

            DB::table('group_student')->insert($insertData);
        }
    }

    /**
     * Build assignment result
     *
     * @param int $assignedCount Number of successfully assigned students
     * @param int $alreadyAssignedCount Number of already assigned students
     * @param array $errors Errors encountered
     * @return array
     */
    private function buildResult(int $assignedCount, int $alreadyAssignedCount, array $errors): array
    {
        return [
            'assigned_count' => $assignedCount,
            'already_assigned_count' => $alreadyAssignedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Build empty result (no assignments)
     *
     * @param array $errors Errors encountered
     * @return array
     */
    private function buildEmptyResult(array $errors): array
    {
        return $this->buildResult(0, 0, $errors);
    }

    /**
     * Assign a single student to a group
     *
     * @param Group $group Target group
     * @param int $studentId Student ID to assign
     * @return array Result with assigned status and message
     * @throws \InvalidArgumentException|\Exception
     */
    /**
     * Assign a single student to a group
     *
     * @param Group $group Target group
     * @param int $studentId Student ID to assign
     * @return array Result with assigned status and message
     * @throws \InvalidArgumentException|\Exception
     */
    public function assignStudentToGroup(Group $group, int $studentId): array
    {
        $student = User::findOrFail($studentId);

        if (!$student->hasRole('student')) {
            throw new \InvalidArgumentException("User is not a student.");
        }

        if (!$group->hasAvailableSlots()) {
            throw new \Exception("Group '{$group->name}' is full.");
        }

        $existingActiveAssignment = $student->groups()
            ->wherePivot('group_id', $group->id)
            ->wherePivot('is_active', true)
            ->exists();

        if ($existingActiveAssignment) {
            return ['assigned' => false, 'message' => 'Already assigned'];
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

        return ['assigned' => true, 'message' => 'Assigned successfully'];
    }

    /**
     * Remove a student from a group
     *
     * @param Group $group Source group
     * @param User $student Student to remove
     * @return bool
     * @throws \InvalidArgumentException
     */
    /**
     * Remove a student from a group
     *
     * @param Group $group Source group
     * @param User $student Student to remove
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function removeStudentFromGroup(Group $group, User $student): bool
    {
        return DB::transaction(function () use ($group, $student) {
            $assignment = $student->groups()
                ->where('group_id', $group->id)
                ->wherePivot('is_active', true)
                ->first();

            if (!$assignment) {
                throw new \InvalidArgumentException("Student is not assigned to this group.");
            }

            $student->groups()->updateExistingPivot($group->id, [
                'is_active' => false,
                'left_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Get students not assigned to any active group
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableStudents(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role('student')
            ->whereDoesntHave('groups', function ($query) {
                $query->where('group_student.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get form data for group creation/edit
     *
     * @return array
     */
    public function getFormData(): array
    {
        return [
            'levels' => Level::options(),
            'available_students' => $this->getAvailableStudents(),
        ];
    }

    /**
     * Get levels for filtering
     *
     * @return array
     */
    public function getLevelsForFilters(): array
    {
        return Level::options();
    }

    /**
     * Deactivate all active group assignments for a student
     *
     * @param User $student Student to deactivate assignments
     * @return void
     */
    private function deactivateCurrentGroupAssignments(User $student): void
    {
        DB::table('group_student')
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => now(),
            ]);
    }

    /**
     * Activate multiple groups in bulk
     *
     * @param array $groupIds Group IDs to activate
     * @return array Result with activated and already active counts
     */
    /**
     * Activate multiple groups in bulk
     *
     * @param array $groupIds Group IDs to activate
     * @return array Result with activated and already active counts
     */
    public function bulkActivate(array $groupIds): array
    {
        return DB::transaction(function () use ($groupIds) {
            $alreadyActiveCount = Group::whereIn('id', $groupIds)
                ->where('is_active', true)
                ->count();

            $activatedCount = Group::whereIn('id', $groupIds)
                ->where('is_active', false)
                ->update(['is_active' => true]);

            return [
                'activated_count' => $activatedCount,
                'already_active_count' => $alreadyActiveCount,
            ];
        });
    }

    /**
     * Deactivate multiple groups in bulk
     *
     * @param array $groupIds Group IDs to deactivate
     * @return array Result with deactivated and already inactive counts
     */
    public function bulkDeactivate(array $groupIds): array
    {
        return DB::transaction(function () use ($groupIds) {
            $alreadyInactiveCount = Group::whereIn('id', $groupIds)
                ->where('is_active', false)
                ->count();

            $deactivatedCount = Group::whereIn('id', $groupIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return [
                'deactivated_count' => $deactivatedCount,
                'already_inactive_count' => $alreadyInactiveCount,
            ];
        });
    }

    /**
     * Remove multiple students from a group in bulk
     *
     * @param Group $group Source group
     * @param array $studentIds Student IDs to remove
     * @return array Result with removed and not in group counts
     */
    public function bulkRemoveStudentsFromGroup(Group $group, array $studentIds): array
    {
        return DB::transaction(function () use ($group, $studentIds) {
            $activeAssignmentsCount = DB::table('group_student')
                ->where('group_id', $group->id)
                ->whereIn('student_id', $studentIds)
                ->where('is_active', true)
                ->count();

            $notInGroupCount = count($studentIds) - $activeAssignmentsCount;

            $removedCount = DB::table('group_student')
                ->where('group_id', $group->id)
                ->whereIn('student_id', $studentIds)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'left_at' => now(),
                ]);

            return [
                'removed_count' => $removedCount,
                'not_in_group_count' => $notInGroupCount,
            ];
        });
    }
}
