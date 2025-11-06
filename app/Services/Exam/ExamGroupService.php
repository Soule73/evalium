<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Exam Group Service
 *
 * Manages the exam_group pivot table relationships.
 * Handles CRUD operations for exam-group assignments only.
 *
 * Single Responsibility: Manage exam-group pivot relationships
 * Statistics and detailed queries delegated to ExamStatsService
 */
class ExamGroupService
{
    /**
     * Assign an exam to multiple groups with bulk operations
     *
     * Optimized to avoid N+1 queries by using bulk operations.
     * Validates group existence and prevents duplicate assignments.
     *
     * @param  Exam  $exam  The exam to assign
     * @param  array<int>  $groupIds  Array of group IDs to assign the exam to
     * @param  int|null  $teacherId  ID of the teacher making the assignment (defaults to authenticated user)
     * @return array{assigned_count: int, already_assigned_count: int} Assignment results
     */
    public function assignExamToGroups(Exam $exam, array $groupIds, ?int $teacherId = null): array
    {
        $teacherId = $teacherId ?? Auth::id();

        DB::beginTransaction();
        try {
            $validGroupIds = Group::whereIn('id', $groupIds)->pluck('id')->toArray();

            if (empty($validGroupIds)) {
                DB::commit();

                return [
                    'assigned_count' => 0,
                    'already_assigned_count' => 0,
                ];
            }

            $alreadyAssignedIds = $exam->groups()
                ->whereIn('group_id', $validGroupIds)
                ->pluck('group_id')
                ->toArray();

            $newGroupIds = array_diff($validGroupIds, $alreadyAssignedIds);

            $assignedCount = 0;
            $alreadyAssignedCount = count($alreadyAssignedIds);

            if (! empty($newGroupIds)) {
                $attachData = collect($newGroupIds)->mapWithKeys(function ($groupId) use ($teacherId) {
                    return [
                        $groupId => [
                            'assigned_by' => $teacherId,
                            'assigned_at' => now(),
                        ],
                    ];
                })->toArray();

                $exam->groups()->attach($attachData);
                $assignedCount = count($newGroupIds);
            }

            DB::commit();

            return [
                'assigned_count' => $assignedCount,
                'already_assigned_count' => $alreadyAssignedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove an exam from a specific group
     *
     * @param  Exam  $exam  The exam to remove
     * @param  Group  $group  The group to remove the exam from
     * @return bool True if the exam was successfully removed, false otherwise
     */
    public function removeExamFromGroup(Exam $exam, Group $group): bool
    {
        return $exam->groups()->detach($group->id) > 0;
    }

    /**
     * Remove an exam from multiple groups
     *
     * @param  Exam  $exam  The exam to remove
     * @param  array<int>  $groupIds  Array of group IDs to remove the exam from
     * @return int Number of groups the exam was removed from
     */
    public function removeExamFromGroups(Exam $exam, array $groupIds): int
    {
        return $exam->groups()->detach($groupIds);
    }

    /**
     * Get all groups assigned to an exam
     *
     * Includes level information and active student counts.
     *
     * @param  Exam  $exam  The exam
     * @return \Illuminate\Database\Eloquent\Collection Collection of groups with relationships
     */
    public function getGroupsForExam(Exam $exam)
    {
        return $exam->groups()
            ->with(['level', 'activeStudents'])
            ->withCount('activeStudents')
            ->get();
    }

    /**
     * Get all exams assigned to a group
     *
     * Includes teacher information and question counts.
     *
     * @param  Group  $group  The group
     * @return \Illuminate\Database\Eloquent\Collection Collection of exams with relationships
     */
    public function getExamsForGroup(Group $group)
    {
        return $group->exams()
            ->with('teacher')
            ->withCount('questions')
            ->get();
    }

    /**
     * Check if an exam is assigned to a specific group
     *
     * @param  Exam  $exam  The exam
     * @param  Group  $group  The group
     * @return bool True if the exam is assigned to the group
     */
    public function isExamAssignedToGroup(Exam $exam, Group $group): bool
    {
        return $exam->groups()->where('group_id', $group->id)->exists();
    }

    /**
     * Get all active groups available for exam assignment
     *
     * Returns only active groups that are not already assigned to the exam.
     *
     * @param  Exam  $exam  The exam
     * @return \Illuminate\Database\Eloquent\Collection Collection of available groups
     */
    public function getAvailableGroupsForExam(Exam $exam)
    {
        $assignedGroupIds = $exam->groups()->pluck('group_id')->toArray();

        return Group::query()
            ->where('is_active', true)
            ->whereNotIn('id', $assignedGroupIds)
            ->with(['level'])
            ->withCount('activeStudents')
            ->orderBy('academic_year', 'desc')
            ->get();
    }

    /**
     * Get the total number of students who have access to an exam
     *
     * Calculates the sum of active students across all assigned groups.
     *
     * @param  Exam  $exam  The exam
     * @return int Total number of students with exam access
     */
    public function getTotalStudentsForExam(Exam $exam): int
    {
        return $exam->groups()
            ->withCount('activeStudents')
            ->get()
            ->sum('active_students_count');
    }
}
