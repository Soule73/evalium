<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
     * @param  User  $studentId
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
            $assignment = new ExamAssignment;
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
     */
    public function getAssignmentWithRelations(Exam $exam, int $studentId, array $relations = []): ExamAssignment
    {
        $query = $exam->assignments()->where('student_id', $studentId);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->firstOrFail();
    }

    /**
     * Get all assignments for an exam with pagination
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
     * @return Collection<int, ExamAssignment>
     */
    public function getAllAssignments(Exam $exam, array $relations = []): Collection
    {
        $query = $exam->assignments();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get assignments for a student
     *
     * @return Collection<int, ExamAssignment>
     */
    public function getStudentAssignments(int $studentId, array $relations = []): Collection
    {
        $query = ExamAssignment::where('student_id', $studentId)
            ->orderBy('assigned_at', 'desc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Create or get existing assignment
     *
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
            'was_created' => $assignment->wasRecentlyCreated,
        ];
    }

    /**
     * Delete assignment
     */
    public function delete(ExamAssignment $assignment): bool
    {
        return $assignment->delete();
    }

    /**
     * Get all students in a group with their exam assignments (real or virtual)
     *
     * @param  Exam  $exam  The exam instance
     * @param  \App\Models\Group  $group  The group instance
     * @param  int  $perPage  Number of items per page
     * @param  string|null  $search  Search term for student name/email
     * @param  string|null  $filterStatus  Filter by assignment status
     * @return LengthAwarePaginator
     */
    public function getGroupStudentsWithAssignments(
        Exam $exam,
        \App\Models\Group $group,
        int $perPage = 10,
        ?string $search = null,
        ?string $filterStatus = null
    ): LengthAwarePaginator {
        $studentsQuery = $group->students()
            ->wherePivot('is_active', true);

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filterStatus) {
            if ($filterStatus === 'assigned') {
                $studentsQuery->whereDoesntHave('examAssignments', function ($q) use ($exam) {
                    $q->where('exam_id', $exam->id);
                });
            } else {
                $studentsQuery->whereHas('examAssignments', function ($q) use ($exam, $filterStatus) {
                    $q->where('exam_id', $exam->id)
                        ->where('status', $filterStatus);
                });
            }
        }

        $students = $studentsQuery->paginate($perPage)->withQueryString();

        $studentIds = $students->pluck('id')->toArray();
        $existingAssignments = $exam->assignments()
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $students->getCollection()->transform(function ($student) use ($exam, $existingAssignments) {
            if (isset($existingAssignments[$student->id])) {
                $assignment = $existingAssignments[$student->id];
            } else {
                $assignment = new ExamAssignment;
                $assignment->exam_id = $exam->id;
                $assignment->student_id = $student->id;
                $assignment->status = 'assigned';
                $assignment->assigned_at = now();
                $assignment->exists = false;
            }

            $assignment->setRelation('student', $student);
            $assignment->setRelation('exam', $exam);

            return $assignment;
        });

        return $students;
    }
}
