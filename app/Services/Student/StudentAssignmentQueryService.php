<?php

namespace App\Services\Student;

use App\Models\AssessmentAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Student Assignment Query Service
 *
 * Handles querying student assignments with various filters and relationships.
 * Single Responsibility: Query student assignments only.
 */
class StudentAssignmentQueryService
{
    /**
     * Get lightweight assignments for a student (without heavy relationships)
     *
     * @param  User  $student  The student user
     * @param  array  $filters  Optional filters (status, search, etc.)
     * @return Collection Collection of assignments
     */
    public function getAssignmentsForStudentLight(User $student, array $filters = []): Collection
    {
        $query = AssessmentAssignment::where('student_id', $student->id)
            ->with([
                'assessment:id,title,subject,duration,total_points',
                'assessment.teacher:id,name',
            ])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->whereHas('assessment', function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('subject', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->get();
    }

    /**
     * Get paginated lightweight assignments for a student (without heavy relationships)
     *
     * @param  User  $student  The student user
     * @param  array  $filters  Optional filters (status, search, etc.)
     * @param  int  $perPage  Number of items per page
     * @return LengthAwarePaginator Paginated assignments
     */
    public function getAssignmentsForStudentLightPaginated(User $student, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = AssessmentAssignment::where('student_id', $student->id)
            ->with([
                'assessment:id,title,subject,duration,total_points',
                'assessment.teacher:id,name',
            ])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->whereHas('assessment', function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('subject', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get detailed assignments with all relationships
     *
     * @param  User  $student  The student user
     * @param  array  $filters  Optional filters
     * @return Collection Collection of assignments with full relationships
     */
    public function getAssignmentsForStudent(User $student, array $filters = []): Collection
    {
        $query = AssessmentAssignment::where('student_id', $student->id)
            ->with([
                'assessment.questions.choices',
                'assessment.teacher',
                'answers',
            ])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        return $query->get();
    }

    /**
     * Get upcoming assignments (not submitted)
     *
     * @param  User  $student  The student user
     * @param  int  $limit  Number of assignments to return
     * @return Collection Collection of upcoming assignments
     */
    public function getUpcomingAssignments(User $student, int $limit = 5): Collection
    {
        return AssessmentAssignment::where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->with('assessment:id,title,subject,duration,total_points')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get completed assignments
     *
     * @param  User  $student  The student user
     * @param  int  $limit  Number of assignments to return
     * @return Collection Collection of completed assignments
     */
    public function getCompletedAssignments(User $student, int $limit = 10): Collection
    {
        return AssessmentAssignment::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->with('assessment:id,title,subject,total_points')
            ->orderBy('submitted_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if student has access to an assessment
     *
     * @param  User  $student  The student user
     * @param  int  $assessmentId  The assessment ID
     * @return bool True if student has an assignment for this assessment
     */
    public function hasAccessToAssessment(User $student, int $assessmentId): bool
    {
        return AssessmentAssignment::where('student_id', $student->id)
            ->where('assessment_id', $assessmentId)
            ->exists();
    }

    /**
     * Get or create an assignment for a student
     *
     * @param  User  $student  The student user
     * @param  int  $assessmentId  The assessment ID
     * @return AssessmentAssignment|null The assignment or null if not authorized
     */
    public function getOrCreateAssignment(User $student, int $assessmentId): ?AssessmentAssignment
    {
        $assignment = AssessmentAssignment::firstOrCreate([
            'student_id' => $student->id,
            'assessment_id' => $assessmentId,
        ]);

        return $assignment;
    }

    /**
     * Apply status filter to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder
     * @param  string  $status  The status filter (not_submitted, submitted, graded)
     */
    protected function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'not_submitted' => $query->whereNull('submitted_at'),
            'submitted' => $query->whereNotNull('submitted_at')->whereNull('graded_at'),
            'graded' => $query->whereNotNull('graded_at'),
            default => null,
        };
    }
}
