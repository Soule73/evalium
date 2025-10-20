<?php

namespace App\Services\Teacher;

use App\Models\Exam;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Service pour gérer l'assignation des examens aux groupes
 */
class ExamGroupService
{
    /**
     * Assigner un examen à un ou plusieurs groupes
     *
     * @param Exam $exam
     * @param array<int> $groupIds
     * @param int|null $teacherId
     * @return array{assigned_count: int, already_assigned_count: int}
     */
    public function assignExamToGroups(Exam $exam, array $groupIds, ?int $teacherId = null): array
    {
        $teacherId = $teacherId ?? Auth::id();
        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($groupIds as $groupId) {
                // Vérifier si le groupe existe
                $group = Group::find($groupId);
                if (!$group) {
                    continue;
                }

                // Vérifier si l'examen est déjà assigné à ce groupe
                if ($exam->groups()->where('group_id', $groupId)->exists()) {
                    $alreadyAssignedCount++;
                    continue;
                }

                // Assigner l'examen au groupe
                $exam->groups()->attach($groupId, [
                    'assigned_by' => $teacherId,
                    'assigned_at' => now(),
                ]);

                $assignedCount++;
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
     * Retirer un examen d'un groupe
     *
     * @param Exam $exam
     * @param Group $group
     * @return bool
     */
    public function removeExamFromGroup(Exam $exam, Group $group): bool
    {
        return $exam->groups()->detach($group->id) > 0;
    }

    /**
     * Retirer un examen de plusieurs groupes
     *
     * @param Exam $exam
     * @param array<int> $groupIds
     * @return int Nombre de groupes retirés
     */
    public function removeExamFromGroups(Exam $exam, array $groupIds): int
    {
        return $exam->groups()->detach($groupIds);
    }

    /**
     * Obtenir tous les groupes assignés à un examen
     *
     * @param Exam $exam
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGroupsForExam(Exam $exam)
    {
        return $exam->groups()
            ->with(['level', 'activeStudents'])
            ->withCount('activeStudents')
            ->get();
    }

    /**
     * Obtenir tous les examens assignés à un groupe
     *
     * @param Group $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExamsForGroup(Group $group)
    {
        return $group->exams()
            ->with('teacher')
            ->withCount('questions')
            ->get();
    }

    /**
     * Vérifier si un examen est assigné à un groupe
     *
     * @param Exam $exam
     * @param Group $group
     * @return bool
     */
    public function isExamAssignedToGroup(Exam $exam, Group $group): bool
    {
        return $exam->groups()->where('group_id', $group->id)->exists();
    }

    /**
     * Obtenir tous les groupes disponibles pour l'assignation
     * (groupes actifs non encore assignés à cet examen)
     *
     * @param Exam $exam
     * @return \Illuminate\Database\Eloquent\Collection
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
     * Obtenir le nombre total d'étudiants qui auront accès à cet examen
     *
     * @param Exam $exam
     * @return int
     */
    public function getTotalStudentsForExam(Exam $exam): int
    {
        return $exam->groups()
            ->withCount('activeStudents')
            ->get()
            ->sum('active_students_count');
    }
}
