<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Assignment Repository - Data access layer for exam assignments
 * 
 * Responsibilities:
 * - Query building for assignments
 * - No business logic (belongs in services)
 * - Reusable queries across the application
 */
class AssignmentRepository
{
    /**
     * Get assignment by exam and student (including virtual assignments via groups)
     *
     * @param Exam $exam
     * @param User $studentId
     * @return ExamAssignment|null
     */
    public function findByExamAndStudent(Exam $exam, User $student): ?ExamAssignment
    {

        $assignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->orderBy('assigned_at', 'desc')
            ->first();

        if ($assignment) {
            return $assignment;
        }

        if ($this->studentHasAccessViaGroup($exam, $student)) {
            $assignment = new ExamAssignment();
            $assignment->exam_id = $exam->id;
            $assignment->student_id = $student->id;
            $assignment->status = null;
            $assignment->assigned_at = now();
            $assignment->setRelation('exam', $exam);
            $assignment->setRelation('student', $student);
            $assignment->exists = false;

            return $assignment;
        }

        return null;
    }

    /**
     * Check if a student has access to an exam via their active group
     *
     * @param Exam $exam
     * @param User $student
     * @return bool
     */
    private function studentHasAccessViaGroup(Exam $exam, User $student): bool
    {

        $activeGroupIds = $student->activeGroups()->pluck('groups.id')->toArray();

        if (empty($activeGroupIds)) {
            return false;
        }

        return $exam->groups()
            ->whereIn('groups.id', $activeGroupIds)
            ->exists();
    }

    /**
     * Get started assignment (not submitted)
     *
     * @param Exam $exam
     * @param int $studentId
     * @return ExamAssignment|null
     */
    public function findStartedAssignment(Exam $exam, int $studentId): ?ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $studentId)
            ->whereNotNull('started_at')
            ->whereNull('submitted_at')
            ->first();
    }

    /**
     * Get completed assignment
     *
     * @param Exam $exam
     * @param int $studentId
     * @param array $statuses
     * @return ExamAssignment|null
     */
    public function findCompletedAssignment(Exam $exam, int $studentId, array $statuses = ['submitted', 'graded']): ?ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $studentId)
            ->whereIn('status', $statuses)
            ->first();
    }

    /**
     * Get assignment with relationships
     *
     * @param Exam $exam
     * @param int $studentId
     * @param array $relations
     * @return ExamAssignment
     */
    public function getAssignmentWithRelations(Exam $exam, int $studentId, array $relations = []): ExamAssignment
    {
        $query = $exam->assignments()->where('student_id', $studentId);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->firstOrFail();
    }

    /**
     * Get all assignments for an exam with pagination
     *
     * @param Exam $exam
     * @param int $perPage
     * @param string|null $search
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function getPaginatedAssignments(
        Exam $exam,
        int $perPage = 10,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $query = $exam->assignments()
            ->with('student')
            ->orderBy('assigned_at', 'desc');

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all assignments for an exam
     *
     * @param Exam $exam
     * @param array $relations
     * @return Collection<int, ExamAssignment>
     */
    public function getAllAssignments(Exam $exam, array $relations = []): Collection
    {
        $query = $exam->assignments();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get assignments for a student
     *
     * @param int $studentId
     * @param array $relations
     * @return Collection<int, ExamAssignment>
     */
    public function getStudentAssignments(int $studentId, array $relations = []): Collection
    {
        $query = ExamAssignment::where('student_id', $studentId)
            ->orderBy('assigned_at', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Create or get existing assignment
     *
     * @param Exam $exam
     * @param int $studentId
     * @return array{assignment: ExamAssignment, was_created: bool}
     */
    public function firstOrCreate(Exam $exam, int $studentId): array
    {
        $assignment = ExamAssignment::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
        ], [
            'assigned_at' => now(),
        ]);

        return [
            'assignment' => $assignment,
            'was_created' => $assignment->wasRecentlyCreated
        ];
    }

    /**
     * Delete assignment
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function delete(ExamAssignment $assignment): bool
    {
        return $assignment->delete();
    }
}
