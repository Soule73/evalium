<?php

namespace App\Services\Core;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Exam Statistics Service
 *
 * Centralizes ALL statistics calculations for exams, groups, and students.
 * Eliminates duplication previously spread across ExamGroupService,
 * ExamAssignmentService, and ExamQueryService.
 *
 * Single Responsibility: Calculate statistics only
 * No queries, no business logic, just pure calculations.
 */
class ExamStatsService
{
    /**
     * Calculate comprehensive statistics for an exam
     *
     * @param  Exam  $exam  The exam to calculate stats for
     * @return array Statistics with counts and rates
     */
    public function calculateExamStats(Exam $exam): array
    {
        $allAssignments = $exam->assignments()->get();

        $totalAssigned = $allAssignments->count();

        $completed = $allAssignments->whereIn('status', ['submitted', 'graded'])->count();

        $inProgress = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStarted = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $averageScore = $allAssignments->whereNotNull('score')->avg('score');

        $completionRate = $totalAssigned > 0 ? ($completed / $totalAssigned) * 100 : 0;

        return [
            'total_assigned' => $totalAssigned,
            'total_submitted' => $completed,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'completion_rate' => round($completionRate, 2),
            'average_score' => $averageScore ? round($averageScore, 2) : null,
        ];
    }

    /**
     * Calculate statistics for a specific group in an exam
     *
     * @param  Exam  $exam  The exam
     * @param  Group  $group  The group
     * @return array Group-specific statistics
     */
    public function calculateGroupStats(Exam $exam, Group $group): array
    {
        $allStudentIds = $group->students()
            ->wherePivot('is_active', true)
            ->pluck('users.id')
            ->toArray();

        $totalStudents = count($allStudentIds);

        $allAssignments = $exam->assignments()
            ->whereIn('student_id', $allStudentIds)
            ->get();

        $inProgress = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $submitted = $allAssignments->filter(function ($assignment) {
            return $assignment->submitted_at !== null;
        })->count();

        $notStarted = $totalStudents - $inProgress - $submitted;

        $averageScore = $allAssignments->whereNotNull('score')->avg('score');

        return [
            'total_students' => $totalStudents,
            'completed' => $submitted,
            'started' => $inProgress,
            'assigned' => max(0, $notStarted),
            'average_score' => $averageScore ? round($averageScore, 2) : null,
        ];
    }

    /**
     * Calculate statistics for exam assignments including potential students
     *
     * Takes into account students in assigned groups who haven't started yet.
     *
     * @param  Exam  $exam  The exam
     * @param  Collection  $assignedGroups  Groups assigned to the exam
     * @return array Enhanced statistics
     */
    public function calculateExamStatsWithGroups(Exam $exam, Collection $assignedGroups): array
    {
        $totalStudentsInGroups = $assignedGroups->sum(function ($group) {
            return $group->activeStudents->count();
        });

        $allAssignments = $exam->assignments()->get();
        $assignedStudentsCount = $allAssignments->count();

        $notAssignedYet = max(0, $totalStudentsInGroups - $assignedStudentsCount);

        $inProgress = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStartedWithAssignment = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $completed = $allAssignments->whereIn('status', ['submitted', 'graded'])->count();

        $averageScore = $allAssignments->whereNotNull('score')->avg('score');

        $completionRate = $totalStudentsInGroups > 0
            ? ($completed / $totalStudentsInGroups) * 100
            : 0;

        return [
            'total_assigned' => $totalStudentsInGroups,
            'total_submitted' => $completed,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStartedWithAssignment + $notAssignedYet,
            'completion_rate' => round($completionRate, 2),
            'average_score' => $averageScore ? round($averageScore, 2) : null,
        ];
    }

    /**
     * Calculate student progress across all exams
     *
     * @param  User  $student  The student
     * @return array Student progress statistics
     */
    public function calculateStudentProgress(User $student): array
    {
        $examAssignments = $student->examAssignments()
            ->with(['exam' => function ($query) {
                $query->withCount('questions');
            }])
            ->get();

        $totalExams = $examAssignments->count();

        $completedAssignments = $examAssignments->whereIn('status', ['submitted', 'graded']);
        $completedCount = $completedAssignments->count();

        $pendingCount = $examAssignments->filter(function ($assignment) {
            return $assignment->started_at === null
                || ($assignment->started_at !== null && $assignment->submitted_at === null);
        })->count();

        $totalScore = $completedAssignments->whereNotNull('score')->sum('score');

        $totalPossible = $completedAssignments->sum(function ($assignment) {
            return $assignment->exam && $assignment->exam->questions
                ? $assignment->exam->questions->sum('points')
                : 0;
        });

        $averageScore = $totalPossible > 0
            ? round(($totalScore / $totalPossible) * 20, 2)
            : 0.0;

        return [
            'total_exams' => $totalExams,
            'completed_exams' => $completedCount,
            'pending_exams' => $pendingCount,
            'average_score' => $averageScore,
        ];
    }

    /**
     * Calculate completion rate for a collection of assignments
     *
     * @param  Collection  $assignments  Collection of exam assignments
     * @param  int  $totalPossible  Total possible assignments (e.g., students in group)
     * @return float Completion rate as percentage
     */
    public function calculateCompletionRate(Collection $assignments, int $totalPossible): float
    {
        if ($totalPossible === 0) {
            return 0.0;
        }

        $completed = $assignments->whereIn('status', ['submitted', 'graded'])->count();

        return round(($completed / $totalPossible) * 100, 2);
    }

    /**
     * Calculate average score from a collection of assignments
     *
     * @param  Collection  $assignments  Collection of exam assignments
     * @return float|null Average score or null if no scores
     */
    public function calculateAverageScore(Collection $assignments): ?float
    {
        $averageScore = $assignments->whereNotNull('score')->avg('score');

        return $averageScore ? round($averageScore, 2) : null;
    }

    /**
     * Count assignments by status
     *
     * @param  Collection  $assignments  Collection of exam assignments
     * @return array Counts by status
     */
    public function countByStatus(Collection $assignments): array
    {
        return [
            'not_started' => $assignments->filter(fn($a) => $a->started_at === null)->count(),
            'in_progress' => $assignments->filter(
                fn($a) => $a->started_at !== null && $a->submitted_at === null
            )->count(),
            'submitted' => $assignments->where('status', 'submitted')->count(),
            'graded' => $assignments->where('status', 'graded')->count(),
        ];
    }

    /**
     * Calculate teacher dashboard statistics
     *
     * @param  User  $teacher  The teacher
     * @param  Collection  $exams  Teacher's exams with questions loaded
     * @return array Dashboard statistics
     */
    public function calculateTeacherDashboardStats(User $teacher, Collection $exams): array
    {
        $examIds = $exams->pluck('id');

        $assignments = ExamAssignment::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'graded'])
            ->whereNotNull('score')
            ->get();

        $totalQuestions = $exams->sum('questions_count');
        $studentsEvaluated = $assignments->unique('student_id')->count();

        $totalScore = $assignments->sum('score');
        $totalPossible = $assignments->sum(function ($assignment) use ($exams) {
            $exam = $exams->firstWhere('id', $assignment->exam_id);

            return $exam?->total_points ?? 0;
        });

        $averageScore = $totalPossible > 0
            ? round(($totalScore / $totalPossible) * 100, 2)
            : 0;

        return [
            'total_exams' => $exams->count(),
            'total_questions' => $totalQuestions,
            'students_evaluated' => $studentsEvaluated,
            'average_score' => $averageScore,
        ];
    }
}
