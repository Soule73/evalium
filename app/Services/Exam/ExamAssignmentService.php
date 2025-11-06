<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Exam Assignment Service - Manage exam assignments to students
 *
 * Single Responsibility: Handle exam-student assignments CRUD only
 * Statistics calculations delegated to ExamStatsService
 */
class ExamAssignmentService
{
    /**
     * Get paginated exam assignments with filters
     *
     * @param  Exam  $exam  Target exam
     * @param  int  $perPage  Number of items per page
     * @param  string|null  $search  Search term for student name/email
     * @param  string|null  $status  Filter by assignment status
     */
    public function getExamAssignments(
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
     * Assign an exam to multiple students
     *
     * @param  Exam  $exam  Target exam
     * @param  array  $studentIds  Student IDs to assign
     * @return array Result with assigned counts
     */
    /**
     * Assign an exam to multiple students
     *
     * @param  Exam  $exam  Target exam
     * @param  array  $studentIds  Student IDs to assign
     * @return array Result with assigned counts
     */
    public function assignExamToStudents(Exam $exam, array $studentIds): array
    {
        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        foreach ($studentIds as $studentId) {
            $result = $this->assignExamToStudent($exam, $studentId);

            if ($result['was_created']) {
                $assignedCount++;
            } else {
                $alreadyAssignedCount++;
            }
        }

        return [
            'success' => true,
            'assigned_count' => $assignedCount,
            'already_assigned_count' => $alreadyAssignedCount,
            'total_students' => count($studentIds),
        ];
    }

    /**
     * Assign an exam to all active students in a group
     *
     * @param  Exam  $exam  Target exam
     * @param  int  $groupId  Group ID
     * @return array Result with assigned counts
     */
    public function assignExamToGroup(Exam $exam, int $groupId): array
    {
        $group = Group::with('activeStudents')->findOrFail($groupId);

        $studentIds = $group->activeStudents->pluck('id')->toArray();

        return $this->assignExamToStudents($exam, $studentIds);
    }

    /**
     * Assign an exam to a specific student
     *
     * @param  Exam  $exam  Target exam
     * @param  int  $studentId  Student ID
     * @return array Assignment and creation status
     *
     * @throws \InvalidArgumentException
     */
    /**
     * Assign an exam to a specific student
     *
     * @param  Exam  $exam  Target exam
     * @param  int  $studentId  Student ID
     * @return array Assignment and creation status
     *
     * @throws \InvalidArgumentException
     */
    public function assignExamToStudent(Exam $exam, int $studentId): array
    {
        $student = User::find($studentId);
        if (! $student || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException("User with ID {$studentId} is not a valid student.");
        }

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
     * Remove a student's exam assignment
     *
     * @param  Exam  $exam  Target exam
     * @param  User  $student  Student to unassign
     *
     * @throws \InvalidArgumentException
     */
    public function removeStudentAssignment(Exam $exam, User $student): bool
    {
        $assignment = $exam->assignments()->where('student_id', $student->id)->first();

        if (! $assignment) {
            throw new \InvalidArgumentException('This student is not assigned to this exam.');
        }

        return $assignment->delete();
    }

    /**
     * Get student's assignment with all necessary relations
     *
     * @param  Exam  $exam  Target exam
     * @param  User  $student  Target student
     */
    public function getStudentAssignmentWithAnswers(Exam $exam, User $student): ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $student->id)
            ->with(['answers.question.choices', 'answers.choice'])
            ->firstOrFail();
    }

    /**
     * Get student's submitted assignment
     *
     * @param  Exam  $exam  Target exam
     * @param  User  $student  Target student
     */
    public function getSubmittedStudentAssignment(Exam $exam, User $student): ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->with(['answers.question.choices', 'answers.choice'])
            ->firstOrFail();
    }

    /**
     * Get form data for assignment creation
     *
     * @param  Exam  $exam  Target exam
     * @return array Form data with students, groups, and assigned IDs
     */
    public function getAssignmentFormData(Exam $exam): array
    {
        $exam->load(['questions', 'assignments.student']);

        $students = User::role('student')
            ->with(['activeGroup'])
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $groups = Group::active()
            ->with(['activeStudents', 'level'])
            ->orderBy('academic_year', 'desc')
            ->get();

        $assignedStudentIds = $exam->assignments()
            ->pluck('student_id')
            ->toArray();

        return [
            'exam' => $exam,
            'students' => $students,
            'groups' => $groups,
            'alreadyAssigned' => $assignedStudentIds,
            'assignedStudentIds' => $assignedStudentIds,
        ];
    }
}
