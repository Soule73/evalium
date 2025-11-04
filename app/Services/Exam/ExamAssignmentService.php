<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Exam Assignment Service - Manage exam assignments to students
 * 
 * Single Responsibility: Handle exam-student assignments and statistics
 * Dependencies: ExamGroupService for group-based assignments
 */
class ExamAssignmentService
{
    public function __construct(
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Get paginated exam assignments with filters
     *
     * @param Exam $exam Target exam
     * @param int $perPage Number of items per page
     * @param string|null $search Search term for student name/email
     * @param string|null $status Filter by assignment status
     * @return LengthAwarePaginator
     */
    /**
     * Get paginated exam assignments with filters
     *
     * @param Exam $exam Target exam
     * @param int $perPage Number of items per page
     * @param string|null $search Search term for student name/email
     * @param string|null $status Filter by assignment status
     * @return LengthAwarePaginator
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
     * Calculate exam assignment statistics
     *
     * @param Exam $exam Target exam
     * @return array Statistics with counts and rates
     */
    /**
     * Calculate exam assignment statistics
     *
     * @param Exam $exam Target exam
     * @return array Statistics with counts and rates
     */
    public function getExamAssignmentStats(Exam $exam): array
    {
        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);

        $totalStudentsInGroups = $assignedGroups->sum(function ($group) {
            return $group->activeStudents->count();
        });

        $allAssignments = $exam->assignments()->get();

        $assignedStudentsCount = $allAssignments->count();

        $notAssignedYet = $totalStudentsInGroups - $assignedStudentsCount;

        $inProgressCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStartedCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $completedCount = $allAssignments->whereIn('status', ['submitted', 'graded'])->count();

        return [
            'total_assigned' => $totalStudentsInGroups,
            'total_submitted' => $completedCount,
            'completed' => $completedCount,
            'in_progress' => $inProgressCount,
            'not_started' => $notStartedCount + $notAssignedYet,
            'completion_rate' => $totalStudentsInGroups > 0 ?
                ($completedCount / $totalStudentsInGroups) * 100 : 0,
            'average_score' => $allAssignments->whereNotNull('score')->avg('score')
        ];
    }

    /**
     * Assign an exam to multiple students
     *
     * @param Exam $exam Target exam
     * @param array $studentIds Student IDs to assign
     * @return array Result with assigned counts
     */
    /**
     * Assign an exam to multiple students
     *
     * @param Exam $exam Target exam
     * @param array $studentIds Student IDs to assign
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
            'total_students' => count($studentIds)
        ];
    }

    /**
     * Assign an exam to all active students in a group
     *
     * @param Exam $exam Target exam
     * @param int $groupId Group ID
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
     * @param Exam $exam Target exam
     * @param int $studentId Student ID
     * @return array Assignment and creation status
     * @throws \InvalidArgumentException
     */
    /**
     * Assign an exam to a specific student
     *
     * @param Exam $exam Target exam
     * @param int $studentId Student ID
     * @return array Assignment and creation status
     * @throws \InvalidArgumentException
     */
    public function assignExamToStudent(Exam $exam, int $studentId): array
    {
        $student = User::find($studentId);
        if (!$student || !$student->hasRole('student')) {
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
            'was_created' => $assignment->wasRecentlyCreated
        ];
    }

    /**
     * Remove a student's exam assignment
     *
     * @param Exam $exam Target exam
     * @param User $student Student to unassign
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function removeStudentAssignment(Exam $exam, User $student): bool
    {
        $assignment = $exam->assignments()->where('student_id', $student->id)->first();

        if (!$assignment) {
            throw new \InvalidArgumentException("This student is not assigned to this exam.");
        }

        return $assignment->delete();
    }

    /**
     * Get student's assignment with all necessary relations
     *
     * @param Exam $exam Target exam
     * @param User $student Target student
     * @return ExamAssignment
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
     * @param Exam $exam Target exam
     * @param User $student Target student
     * @return ExamAssignment
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
     * @param Exam $exam Target exam
     * @return array Form data with students, groups, and assigned IDs
     */
    /**
     * Get form data for assignment creation
     *
     * @param Exam $exam Target exam
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
            'assignedStudentIds' => $assignedStudentIds
        ];
    }

    /**
     * Get paginated assignments with filters and statistics
     *
     * @param Exam $exam Target exam
     * @param array $params Filter and pagination parameters
     * @return array Assignments, statistics, and assigned groups
     */
    public function getPaginatedAssignments(Exam $exam, array $params): array
    {
        $allAssignments = $exam->assignments()->with('student')->get();

        $filtered = $allAssignments;

        if ($params['search']) {
            $searchTerm = strtolower($params['search']);
            $filtered = $filtered->filter(function ($assignment) use ($searchTerm) {
                return str_contains(strtolower($assignment->student->name ?? ''), $searchTerm) ||
                    str_contains(strtolower($assignment->student->email ?? ''), $searchTerm);
            });
        }

        if ($params['filter_status']) {
            $filtered = $filtered->where('status', $params['filter_status']);
        }

        $sortBy = $params['sort_by'] === 'user_name' ? 'assigned_at' : $params['sort_by'];
        $sortDirection = $params['sort_direction'] === 'desc';
        $sorted = $filtered->sortBy($sortBy, SORT_REGULAR, $sortDirection)->values();

        $currentPage = request()->input('page', 1);
        $perPage = $params['per_page'];
        $paginated = $sorted->forPage($currentPage, $perPage);

        $assignments = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $sorted->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);
        $totalStudentsInGroups = $assignedGroups->sum('active_students_count');

        $assignedStudentsCount = $allAssignments->count();

        $notAssignedYet = $totalStudentsInGroups - $assignedStudentsCount;

        $inProgressCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStartedCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $stats = [
            'total_assigned' => $totalStudentsInGroups,
            'completed' => $allAssignments->whereIn('status', ['submitted', 'graded'])->count(),
            'in_progress' => $inProgressCount,
            'not_started' => $notStartedCount + $notAssignedYet,
            'average_score' => $allAssignments->whereNotNull('score')->avg('score')
        ];

        return [
            'exam' => $exam,
            'assignments' => $assignments,
            'stats' => $stats,
            'assignedGroups' => $assignedGroups
        ];
    }
}
