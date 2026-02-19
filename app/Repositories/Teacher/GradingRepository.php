<?php

namespace App\Repositories\Teacher;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GradingRepository
{
    /**
     * Get assignments for grading with pagination.
     */
    public function getAssignmentsForGrading(Assessment $assessment, int $perPage): LengthAwarePaginator
    {
        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->with(['enrollment.student', 'answers.question'])
            ->paginate($perPage);
    }

    /**
     * Get all enrolled students with their assignment status (including those who haven't started).
     */
    public function getAssignmentsWithEnrolledStudents(
        Assessment $assessment,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $classId = $assessment->classSubject->class_id;

        $query = Enrollment::query()
            ->where('enrollments.class_id', $classId)
            ->where('enrollments.status', 'active')
            ->join('users', 'users.id', '=', 'enrollments.student_id')
            ->leftJoin('assessment_assignments', function ($join) use ($assessment) {
                $join->on('assessment_assignments.enrollment_id', '=', 'enrollments.id')
                    ->where('assessment_assignments.assessment_id', '=', $assessment->id);
            })
            ->select([
                'assessment_assignments.id as assignment_id',
                'assessment_assignments.assessment_id',
                'assessment_assignments.submitted_at',
                'assessment_assignments.score',
                'enrollments.id as enrollment_id',
                'enrollments.student_id',
                'users.name as student_name',
                'users.email as student_email',
            ]);

        if ($search = $filters['search'] ?? null) {
            $like = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(users.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', [$like]);
            });
        }

        $query->orderByRaw(
            'CASE
                WHEN assessment_assignments.submitted_at IS NOT NULL
                     AND assessment_assignments.score IS NULL THEN 0
                WHEN assessment_assignments.submitted_at IS NOT NULL
                     AND assessment_assignments.score IS NOT NULL THEN 1
                ELSE 2
            END'
        );

        $paginator = $query->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(function ($row) use ($assessment) {
                $student = new User;
                $student->id = $row->student_id;
                $student->name = $row->student_name;
                $student->email = $row->student_email;

                if ($row->assignment_id !== null) {
                    $assignment = new AssessmentAssignment;
                    $assignment->id = $row->assignment_id;
                    $assignment->assessment_id = $row->assessment_id ?? $assessment->id;
                    $assignment->enrollment_id = $row->enrollment_id;
                    $assignment->submitted_at = $row->submitted_at;
                    $assignment->score = $row->score;
                    $assignment->setRelation('student', $student);
                    $assignment->is_virtual = false;

                    return $assignment;
                }

                return (object) [
                    'id' => null,
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $row->enrollment_id,
                    'student' => $student,
                    'submitted_at' => null,
                    'score' => null,
                    'is_virtual' => true,
                ];
            })
        );

        return $paginator;
    }

    /**
     * Get assignment for a specific student with full answer details.
     */
    public function getAssignmentForStudent(Assessment $assessment, User $student): AssessmentAssignment
    {
        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->with(['answers.question', 'answers.choice', 'enrollment'])
            ->firstOrFail();
    }

    /**
     * Load assessment relationships for grading index.
     */
    public function loadAssessmentForGradingIndex(Assessment $assessment): Assessment
    {
        return $assessment->load([
            'classSubject.class',
            'questions',
        ]);
    }

    /**
     * Load assessment relationships for grading show.
     */
    public function loadAssessmentForGradingShow(Assessment $assessment): Assessment
    {
        return $assessment->load(['classSubject.class', 'questions.choices']);
    }
}
