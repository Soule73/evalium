<?php

namespace App\Services\Student;

use App\Models\AssessmentAssignment;
use App\Models\User;
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
            ->orderBy('assigned_at', 'desc');

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
            ->orderBy('assigned_at', 'desc');

        if (isset($filters['status'])) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        return $query->get();
    }

    /**
     * Get upcoming assignments (not started)
     *
     * @param  User  $student  The student user
     * @param  int  $limit  Number of assignments to return
     * @return Collection Collection of upcoming assignments
     */
    public function getUpcomingAssignments(User $student, int $limit = 5): Collection
    {
        return AssessmentAssignment::where('student_id', $student->id)
            ->whereNull('started_at')
            ->with('assessment:id,title,subject,duration,total_points')
            ->orderBy('assigned_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get in-progress assignments
     *
     * @param  User  $student  The student user
     * @return Collection Collection of in-progress assignments
     */
    public function getInProgressAssignments(User $student): Collection
    {
        return AssessmentAssignment::where('student_id', $student->id)
            ->whereNotNull('started_at')
            ->whereNull('submitted_at')
            ->with('assessment:id,title,subject,duration')
            ->orderBy('started_at', 'desc')
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
        $assignment = AssessmentAssignment::firstOrCreate(
            [
                'student_id' => $student->id,
                'assessment_id' => $assessmentId,
            ],
            [
                'assigned_at' => now(),
            ]
        );

        return $assignment;
    }

    /**
     * Apply status filter to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder
     * @param  string  $status  The status filter (not_started, in_progress, completed, graded)
     */
    protected function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'not_started' => $query->whereNull('started_at'),
            'in_progress' => $query->whereNotNull('started_at')->whereNull('submitted_at'),
            'completed' => $query->whereNotNull('submitted_at'),
            'graded' => $query->whereNotNull('submitted_at')->whereNotNull('score'),
            default => null,
        };
    }
}
