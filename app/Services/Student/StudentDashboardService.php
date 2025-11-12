<?php

namespace App\Services\Student;

use App\Models\User;
use App\Services\Core\ExamStatsService;
use Illuminate\Support\Collection;

/**
 * Student Dashboard Service
 *
 * Centralizes all dashboard-related data preparation for students.
 * Uses ExamStatsService for statistics calculations.
 * Eliminates duplication from ExamQueryService.
 *
 * Single Responsibility: Prepare student dashboard data only
 */
class StudentDashboardService
{
    public function __construct(
        private readonly ExamStatsService $examStatsService,
        private readonly StudentAssignmentQueryService $studentAssignmentQueryService
    ) {}

    /**
     * Get comprehensive dashboard statistics for a student
     *
     * @param  User  $student  The student
     * @return array Dashboard statistics
     */
    public function getDashboardStats(User $student): array
    {
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $studentProgress = $this->examStatsService->calculateStudentProgress($student);

        $activeGroups = $student->groups()
            ->wherePivot('is_active', true)
            ->count();

        $upcomingExams = $assignments
            ->filter(fn($assignment) => $assignment->started_at === null)
            ->take(5);

        $recentExams = $assignments
            ->filter(fn($assignment) => $assignment->submitted_at !== null)
            ->sortByDesc('submitted_at')
            ->take(5);

        $inProgress = $assignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStarted = $assignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        return [
            'total_assignments' => $studentProgress['total_exams'],
            'completed_assignments' => $studentProgress['completed_exams'],
            'in_progress_assignments' => $inProgress,
            'not_started_assignments' => $notStarted,
            'average_score' => $studentProgress['average_score'],
            'completion_rate' => $studentProgress['total_exams'] > 0
                ? round(($studentProgress['completed_exams'] / $studentProgress['total_exams']) * 100, 2)
                : 0.0,
            'active_groups' => $activeGroups,
            'upcoming_exams' => $upcomingExams->values()->toArray(),
            'recent_exams' => $recentExams->values()->toArray(),
        ];
    }

    /**
     * Get student performance summary
     *
     * @param  User  $student  The student
     * @return array Performance metrics
     */
    public function getPerformanceSummary(User $student): array
    {
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $gradedAssignments = $assignments->filter(function ($assignment) {
            return $assignment->score !== null;
        });

        if ($gradedAssignments->isEmpty()) {
            return [
                'total_graded' => 0,
                'average_score' => null,
                'highest_score' => null,
                'lowest_score' => null,
                'passing_rate' => 0,
            ];
        }

        $scores = $gradedAssignments->pluck('score');
        $passingThreshold = 50;
        $passingCount = $scores->filter(fn($score) => $score >= $passingThreshold)->count();

        return [
            'total_graded' => $gradedAssignments->count(),
            'average_score' => round($scores->avg(), 2),
            'highest_score' => round($scores->max(), 2),
            'lowest_score' => round($scores->min(), 2),
            'passing_rate' => $gradedAssignments->count() > 0
                ? round(($passingCount / $gradedAssignments->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get student activity timeline
     *
     * @param  User  $student  The student
     * @param  int  $limit  Number of recent activities
     * @return Collection Recent activities
     */
    public function getRecentActivity(User $student, int $limit = 10): Collection
    {
        /** @var \App\Models\ExamAssignment $assignments */
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $activities = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->submitted_at) {
                $activities->push([
                    'type' => 'submission',
                    'exam_title' => $assignment->exam->title,
                    'timestamp' => $assignment->submitted_at,
                    'details' => [
                        'score' => $assignment->score,
                        'status' => $assignment->status,
                    ],
                ]);
            }

            if ($assignment->started_at && ! $assignment->submitted_at) {
                $activities->push([
                    'type' => 'started',
                    'exam_title' => $assignment->exam->title,
                    'timestamp' => $assignment->started_at,
                    'details' => [],
                ]);
            }
        }

        return $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }

    /**
     * Get subject-based performance breakdown
     *
     * @param  User  $student  The student
     * @return array Performance by subject
     */
    public function getSubjectPerformance(User $student): array
    {
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $gradedAssignments = $assignments->filter(function ($assignment) {
            return $assignment->score !== null && $assignment->exam;
        });

        if ($gradedAssignments->isEmpty()) {
            return [];
        }

        $bySubject = $gradedAssignments->groupBy(function ($assignment) {
            return $assignment->exam->subject ?? 'General';
        });

        return $bySubject->map(function ($subjectAssignments, $subject) {
            $scores = $subjectAssignments->pluck('score');

            return [
                'subject' => $subject,
                'total_exams' => $subjectAssignments->count(),
                'average_score' => round($scores->avg(), 2),
                'highest_score' => round($scores->max(), 2),
                'lowest_score' => round($scores->min(), 2),
            ];
        })->values()->toArray();
    }

    /**
     * Get monthly progress chart data
     *
     * @param  User  $student  The student
     * @param  int  $months  Number of months to look back
     * @return array Chart data
     */
    public function getMonthlyProgress(User $student, int $months = 6): array
    {
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $gradedAssignments = $assignments->filter(function ($assignment) {
            return $assignment->submitted_at && $assignment->score !== null;
        });

        $startDate = now()->subMonths($months)->startOfMonth();

        $monthlyData = $gradedAssignments
            ->filter(function ($assignment) use ($startDate) {
                return $assignment->submitted_at >= $startDate;
            })
            ->groupBy(function ($assignment) {
                return $assignment->submitted_at->format('Y-m');
            })
            ->map(function ($monthAssignments, $month) {
                $scores = $monthAssignments->pluck('score');

                return [
                    'month' => $month,
                    'count' => $monthAssignments->count(),
                    'average_score' => round($scores->avg(), 2),
                ];
            })
            ->values()
            ->toArray();

        return $monthlyData;
    }

    /**
     * Get group-wise performance comparison
     *
     * @param  User  $student  The student
     * @return array Performance per group
     */
    public function getGroupPerformance(User $student): array
    {
        $groups = $student->groups()
            ->wherePivot('is_active', true)
            ->with('exams')
            ->get();

        return $groups->map(function ($group) use ($student) {
            $groupExamIds = $group->exams->pluck('id');

            $groupAssignments = $student->examAssignments()
                ->whereIn('exam_id', $groupExamIds)
                ->get();

            $completedAssignments = $groupAssignments->filter(function ($assignment) {
                return $assignment->submitted_at !== null;
            });

            $gradedAssignments = $completedAssignments->filter(function ($assignment) {
                return $assignment->score !== null;
            });

            $averageScore = $gradedAssignments->isEmpty()
                ? null
                : round($gradedAssignments->pluck('score')->avg(), 2);

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'total_exams' => $groupExamIds->count(),
                'completed_exams' => $completedAssignments->count(),
                'average_score' => $averageScore,
                'completion_rate' => $groupExamIds->count() > 0
                    ? round(($completedAssignments->count() / $groupExamIds->count()) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get exam status breakdown
     *
     * @param  User  $student  The student
     * @return array Status counts
     */
    public function getExamStatusBreakdown(User $student): array
    {
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

        $statusCounts = [
            'not_started' => 0,
            'in_progress' => 0,
            'submitted' => 0,
            'graded' => 0,
        ];

        foreach ($assignments as $assignment) {
            if ($assignment->started_at === null) {
                $statusCounts['not_started']++;
            } elseif ($assignment->submitted_at === null) {
                $statusCounts['in_progress']++;
            } elseif ($assignment->status === 'graded') {
                $statusCounts['graded']++;
            } else {
                $statusCounts['submitted']++;
            }
        }

        return $statusCounts;
    }
}
