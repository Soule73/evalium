<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class GradingQueryService
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

        $enrolledStudents = Enrollment::where('class_id', $classId)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter();

        $existingAssignments = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->with('enrollment.student')
            ->get()
            ->keyBy(fn($a) => $a->enrollment?->student_id);

        $allStudentData = $enrolledStudents->map(function ($student) use ($assessment, $existingAssignments, $classId) {
            $assignment = $existingAssignments->get($student->id);

            if ($assignment) {
                return $assignment;
            }

            $enrollment = Enrollment::where('student_id', $student->id)
                ->where('class_id', $classId)
                ->first();

            return (object) [
                'id' => null,
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment?->id,
                'student' => $student,
                'submitted_at' => null,
                'score' => null,
                'is_virtual' => true,
            ];
        });

        if ($search = $filters['search'] ?? null) {
            $search = strtolower($search);
            $allStudentData = $allStudentData->filter(function ($item) use ($search) {
                $student = $item->student ?? $item;
                $name = is_object($student) ? ($student->name ?? '') : '';
                $email = is_object($student) ? ($student->email ?? '') : '';

                return str_contains(strtolower($name), $search) ||
                    str_contains(strtolower($email), $search);
            });
        }

        $allStudentData = $allStudentData->sortBy(function ($item) {
            if ($item->submitted_at && $item->score === null) {
                return 0;
            }
            if ($item->submitted_at && $item->score !== null) {
                return 1;
            }

            return 2;
        })->values();

        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = $allStudentData->count();
        $items = $allStudentData->slice($offset, $perPage)->values();

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
