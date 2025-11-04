<?php

namespace App\Services\Core;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use App\Helpers\ExamHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Exam Query Service - Handle complex exam queries and filtering
 * 
 * Single Responsibility: Build and execute complex exam queries
 * Used by controllers that need to fetch exams with specific criteria
 */
class ExamQueryService
{
    /**
     * Get paginated exams for a teacher or all exams for admins
     *
     * @param int $teacherId
     * @param int $perPage
     * @param bool|null $status
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getExamsForTeacher(
        int $teacherId,
        int $perPage = 10,
        ?bool $status = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = Exam::query();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // If user can view any exams (admin), don't filter by teacher
        if (!$currentUser?->can('view any exams')) {
            $query->where('teacher_id', $teacherId);
        }

        return $query
            ->withCount(['questions', 'assignments'])
            ->latest()
            ->when(
                $search,
                fn($query) =>
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->when(
                $status !== null,
                fn($query) =>
                $query->where('is_active', $status)
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all assigned exams for a student (real + virtual via groups)
     *
     * @param User $student
     * @param int|null $perPage
     * @param string|null $status
     * @param string|null $search
     * @return LengthAwarePaginator|Collection
     */
    public function getAssignedExamsForStudent(
        User $student,
        ?int $perPage = 10,
        ?string $status = null,
        ?string $search = null
    ): LengthAwarePaginator|Collection {
        // Load student groups with their active exams if not already loaded
        if (!$student->relationLoaded('groups')) {
            $student->load(['groups' => function ($query) {
                $query->with(['exams' => function ($q) {
                    $q->where('is_active', true);
                }])
                    ->withPivot(['enrolled_at', 'left_at', 'is_active']);
            }]);
        }

        $examIdsFromGroups = $student->groups
            ->filter(fn($group) => $group->pivot && $group->pivot->is_active)
            ->flatMap(function ($group) {
                if (!$group->relationLoaded('exams')) {
                    $group->load(['exams' => function ($q) {
                        $q->where('is_active', true);
                    }]);
                }
                return $group->exams ?? collect([]);
            })
            ->pluck('id')
            ->unique()
            ->toArray();

        $assignmentsQuery = $student->examAssignments()
            ->with(['exam' => function ($query) {
                $query->withCount('questions');
            }])
            ->orderBy('assigned_at', 'desc');

        if ($search) {
            $assignmentsQuery->whereHas(
                'exam',
                fn($q) =>
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($status && !in_array($status, ['in_progress', 'not_started'])) {
            $assignmentsQuery->where('status', $status);
        }

        $existingAssignments = $assignmentsQuery->get();
        $existingExamIds = $existingAssignments->pluck('exam_id')->toArray();

        if (!$perPage) {
            return $existingAssignments;
        }

        $availableExamIds = array_diff($examIdsFromGroups, $existingExamIds);

        if (!empty($availableExamIds)) {
            $availableExams = Exam::whereIn('id', $availableExamIds)
                ->where('is_active', true)
                ->withCount('questions')
                ->when(
                    $search,
                    fn($q) =>
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                )
                ->get();

            // Create virtual assignments for available exams
            $virtualAssignments = $availableExams->map(function ($exam) use ($student) {
                $assignment = new ExamAssignment();
                $assignment->exam_id = $exam->id;
                $assignment->student_id = $student->id;
                $assignment->status = null;
                $assignment->assigned_at = now();
                $assignment->setRelation('exam', $exam);
                $assignment->exists = false;

                return $assignment;
            });

            $allAssignments = $existingAssignments->concat($virtualAssignments);
        } else {
            $allAssignments = $existingAssignments;
        }

        if ($status) {
            $allAssignments = $this->filterAssignmentsByStatus($allAssignments, $status);
        }

        return $this->paginateCollection($allAssignments, $perPage);
    }

    /**
     * Get exams for a student in a specific group
     *
     * @param mixed $group
     * @param User $student
     * @param int|null $perPage
     * @param string|null $status
     * @param string|null $search
     * @param bool|null $isActiveGroup
     * @return LengthAwarePaginator
     */
    public function getExamsForStudentInGroup(
        $group,
        User $student,
        ?int $perPage = 10,
        ?string $status = null,
        ?string $search = null,
        ?bool $isActiveGroup = null
    ): LengthAwarePaginator {
        $examsQuery = $group->exams()
            ->where('is_active', true)
            ->withCount('questions');

        if ($search) {
            $examsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $exams = $examsQuery->get();

        $assignments = ExamAssignment::where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        $allAssignments = $exams->map(function ($exam) use ($student, $assignments) {
            if (isset($assignments[$exam->id])) {
                $assignment = $assignments[$exam->id];
                $assignment->setRelation('exam', $exam);
                return $assignment;
            }

            $assignment = new ExamAssignment();
            $assignment->exam_id = $exam->id;
            $assignment->student_id = $student->id;
            $assignment->status = null;
            $assignment->assigned_at = now();
            $assignment->setRelation('exam', $exam);
            $assignment->exists = false;

            return $assignment;
        });

        if ($status) {
            $allAssignments = $this->filterAssignmentsByStatus($allAssignments, $status);
        }

        return $this->paginateCollection($allAssignments, $perPage ?? 10);
    }

    /**
     * Filter assignments by status
     *
     * @param Collection $assignments
     * @param string $status
     * @return Collection
     */
    private function filterAssignmentsByStatus(Collection $assignments, string $status): Collection
    {
        return match ($status) {
            'in_progress' => $assignments->filter(function ($assignment) {
                return $assignment->started_at !== null && $assignment->submitted_at === null;
            }),
            'not_started' => $assignments->filter(function ($assignment) {
                return $assignment->started_at === null;
            }),
            default => $assignments->where('status', $status),
        };
    }

    /**
     * Paginate a collection manually
     *
     * @param Collection $collection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    private function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $items = $collection->slice($offset, $perPage)->values();
        $total = $collection->count();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );
    }

    /**
     * Get lightweight assignments for dashboard stats
     *
     * @param User $student
     * @return Collection
     */
    public function getAssignedExamsForStudentLight(User $student): Collection
    {
        return $student->examAssignments()
            ->with(['exam' => function ($query) {
                $query->withCount('questions');
            }])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get all groups for a student with exam statistics
     *
     * @param User $student The student user
     * @param int $perPage Number of items per page (default: 15)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated collection of groups with stats
     */
    public function getStudentGroupsWithStats(User $student, int $perPage = 15)
    {
        $studentId = $student->id;

        $query = $student->groups()
            ->withPivot(['enrolled_at', 'left_at', 'is_active'])
            ->with('level')
            ->withCount(['exams' => function ($query) {
                $query->where('is_active', true);
            }])

            ->selectSub(function ($query) use ($studentId) {
                $query->selectRaw('COUNT(*)')
                    ->from('exam_assignments')
                    ->join('exams', 'exam_assignments.exam_id', '=', 'exams.id')
                    ->join('exam_group', 'exams.id', '=', 'exam_group.exam_id')
                    ->whereColumn('exam_group.group_id', 'groups.id')
                    ->where('exam_assignments.student_id', $studentId)
                    ->whereNotNull('exam_assignments.submitted_at')
                    ->whereNull('exams.deleted_at');
            }, 'completed_exams_count')
            ->orderByDesc('is_active')
            ->orderByDesc('enrolled_at');

        $groups = $query->paginate($perPage)->through(function ($group) {
            $group->is_current = (bool) $group->pivot->is_active;

            if (!$group->is_current) {
                unset($group->completed_exams_count);
            }

            return $group;
        });

        return $groups;
    }

    /**
     * Get exam for display with assigned groups
     *
     * @param Exam $exam The exam to display
     * @param \App\Services\Exam\ExamGroupService $examGroupService Service to get groups
     * @return array Exam data with assigned groups
     */
    public function getExamForDisplay(Exam $exam, $examGroupService): array
    {
        $exam->load(['questions.choices']);
        $exam->setAttribute('questions_count', $exam->questions->count());

        $assignedGroups = $examGroupService->getGroupsForExam($exam);

        return [
            'exam' => $exam,
            'assignedGroups' => $assignedGroups
        ];
    }

    /**
     * Get exam for editing with questions and choices
     *
     * @param Exam $exam The exam to edit
     * @return Exam Exam with loaded relations
     */
    public function getExamForEdit(Exam $exam): Exam
    {
        $exam->load(['questions.choices']);

        return $exam;
    }

    /**
     * Get student dashboard statistics
     *
     * @param Collection $examAssignments Collection of exam assignments
     * @return array Dashboard statistics
     */
    public function getStudentDashboardStats(Collection $examAssignments): array
    {
        $totalExams = count($examAssignments);

        $completedExams = ExamHelper::filterCompletedAssignments($examAssignments);
        $countCompletedExams = count($completedExams);

        $pendingExams = count(ExamHelper::filterActiveAssignments($examAssignments));

        $totalScore = $completedExams->whereNotNull('score')->sum('score');

        $totalPossible = $completedExams->sum(function ($assignment) {
            return $assignment->exam && $assignment->exam->questions
                ? $assignment->exam->questions->sum('points')
                : 0;
        });

        $averageScore = $totalPossible > 0 ? round(($totalScore / $totalPossible) * 20, 2) : 0.0;

        return [
            'totalExams' => $totalExams,
            'completedExams' => $countCompletedExams,
            'pendingExams' => $pendingExams,
            'averageScore' => $averageScore,
        ];
    }
}
