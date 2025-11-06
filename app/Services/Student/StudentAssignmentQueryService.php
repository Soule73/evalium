<?php

namespace App\Services\Student;

use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

/**
 * Student Assignment Query Service
 * 
 * Centralizes all queries related to student exam assignments.
 * Handles virtual assignments (exams available via groups but not yet started).
 * Eliminates duplication from ExamQueryService and ExamAssignmentService.
 * 
 * Single Responsibility: Query student assignments only
 */
class StudentAssignmentQueryService
{
    /**
     * Get all assigned exams for a student (real + virtual via groups)
     *
     * @param User $student The student
     * @param int $perPage Items per page
     * @param string|null $status Filter by status
     * @param string|null $search Search term
     * @return LengthAwarePaginator Paginated assignments
     */
    public function getAssignmentsForStudent(
        User $student,
        int $perPage = 10,
        ?string $status = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $student->loadMissing(['groups' => function ($query) {
            $query->with(['exams' => function ($q) {
                $q->where('is_active', true);
            }])
                ->withPivot(['enrolled_at', 'left_at', 'is_active']);
        }]);

        $examIdsFromGroups = $student->groups
            ->filter(fn($group) => $group->pivot && $group->pivot->is_active)
            ->flatMap(function ($group) {
                $group->loadMissing(['exams' => function ($q) {
                    $q->where('is_active', true);
                }]);
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
                fn($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($status && !in_array($status, ['in_progress', 'not_started'])) {
            $assignmentsQuery->where('status', $status);
        }

        $existingAssignments = $assignmentsQuery->get();
        $existingExamIds = $existingAssignments->pluck('exam_id')->toArray();

        $availableExamIds = array_diff($examIdsFromGroups, $existingExamIds);

        if (!empty($availableExamIds)) {
            $availableExams = Exam::whereIn('id', $availableExamIds)
                ->where('is_active', true)
                ->withCount('questions')
                ->when(
                    $search,
                    fn($q) => $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                )
                ->get();

            $virtualAssignments = $availableExams->map(function ($exam) use ($student) {
                return $this->createVirtualAssignment($exam, $student);
            });

            $allAssignments = $existingAssignments->concat($virtualAssignments);
        } else {
            $allAssignments = $existingAssignments;
        }

        if ($status) {
            $allAssignments = $this->filterByStatus($allAssignments, $status);
        }

        return $this->paginateCollection($allAssignments, $perPage);
    }

    /**
     * Get exams for a student in a specific group
     *
     * @param Group $group The group
     * @param User $student The student
     * @param int $perPage Items per page
     * @param string|null $status Filter by status
     * @param string|null $search Search term
     * @return LengthAwarePaginator Paginated assignments
     */
    public function getAssignmentsForStudentInGroup(
        Group $group,
        User $student,
        int $perPage = 10,
        ?string $status = null,
        ?string $search = null
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

            return $this->createVirtualAssignment($exam, $student);
        });

        if ($status) {
            $allAssignments = $this->filterByStatus($allAssignments, $status);
        }

        return $this->paginateCollection($allAssignments, $perPage);
    }

    /**
     * Get student groups with exam statistics
     *
     * @param User $student The student
     * @param int $perPage Items per page
     * @return LengthAwarePaginator Paginated groups with stats
     */
    public function getStudentGroupsWithStats(User $student, int $perPage = 15): LengthAwarePaginator
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

        return $query->paginate($perPage)->through(function ($group) {
            $group->is_current = (bool) $group->pivot->is_active;

            if (!$group->is_current) {
                unset($group->completed_exams_count);
            }

            return $group;
        });
    }

    /**
     * Get lightweight assignments for dashboard stats (no pagination)
     *
     * @param User $student The student
     * @return Collection Collection of assignments
     */
    public function getAssignmentsForStudentLight(User $student): Collection
    {
        return $student->examAssignments()
            ->with(['exam' => function ($query) {
                $query->withCount('questions');
            }])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Create a virtual assignment for an exam available via group
     *
     * @param Exam $exam The exam
     * @param User $student The student
     * @return ExamAssignment Virtual assignment (exists = false)
     */
    private function createVirtualAssignment(Exam $exam, User $student): ExamAssignment
    {
        $assignment = new ExamAssignment();
        $assignment->exam_id = $exam->id;
        $assignment->student_id = $student->id;
        $assignment->status = null;
        $assignment->assigned_at = now();
        $assignment->setRelation('exam', $exam);
        $assignment->exists = false;

        return $assignment;
    }

    /**
     * Filter assignments by status
     *
     * @param Collection $assignments Collection of assignments
     * @param string $status Status to filter by
     * @return Collection Filtered collection
     */
    private function filterByStatus(Collection $assignments, string $status): Collection
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
     * @param Collection $collection Collection to paginate
     * @param int $perPage Items per page
     * @return LengthAwarePaginator Paginated result
     */
    private function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $items = $collection->slice($offset, $perPage)->values();
        $total = $collection->count();

        return new Paginator(
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
     * Check if student can access an exam
     *
     * @param Exam $exam The exam
     * @param User $student The student
     * @return bool True if student can access
     */
    public function canStudentAccessExam(Exam $exam, User $student): bool
    {
        $hasDirectAssignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->exists();

        if ($hasDirectAssignment) {
            return true;
        }

        $hasGroupAccess = $student->groups()
            ->wherePivot('is_active', true)
            ->whereHas('exams', function ($query) use ($exam) {
                $query->where('exams.id', $exam->id);
            })
            ->exists();

        return $hasGroupAccess;
    }

    /**
     * Get or create assignment for student taking an exam
     *
     * @param Exam $exam The exam
     * @param User $student The student
     * @return ExamAssignment|null Assignment or null if no access
     */
    public function getOrCreateAssignmentForExam(Exam $exam, User $student): ?ExamAssignment
    {
        if (!$this->canStudentAccessExam($exam, $student)) {
            return null;
        }

        $assignment = $exam->assignments()
            ->where('student_id', $student->id)
            ->first();

        if (!$assignment) {
            return $this->createVirtualAssignment($exam, $student);
        }

        $assignment->loadMissing('exam');

        return $assignment;
    }
}
