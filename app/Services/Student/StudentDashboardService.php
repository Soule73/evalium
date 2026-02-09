<?php

namespace App\Services\Student;

use App\Models\User;
use App\Services\Core\GradeCalculationService;

/**
 * Student Dashboard Service
 *
 * Orchestrates dashboard data by delegating calculations to GradeCalculationService.
 * NO direct calculations - all grade/average logic is centralized in GradeCalculationService.
 *
 * Single Responsibility: Format and organize dashboard data for presentation
 */
class StudentDashboardService
{
    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService,
        private readonly StudentAssignmentQueryService $studentAssignmentQueryService
    ) {}

    /**
     * Get comprehensive dashboard statistics for a student
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @return array{totalAssessments: int, completedAssessments: int, pendingAssessments: int, averageScore: float|null, upcomingAssessments: array, recentAssessments: array, subjectsBreakdown: array}
     */
    public function getDashboardStats(User $student, ?int $academicYearId = null): array
    {
        $overallStats = $this->gradeCalculationService->getStudentOverallStats($student, $academicYearId);

        $filters = $academicYearId ? ['academic_year_id' => $academicYearId] : [];
        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student, $filters);

        $upcomingAssessments = $assignments
            ->filter(fn($assignment) => $assignment->submitted_at === null)
            ->sortBy('created_at')
            ->take(5)
            ->values();

        $recentAssessments = $assignments
            ->filter(fn($assignment) => $assignment->submitted_at !== null)
            ->sortByDesc('submitted_at')
            ->take(5)
            ->values();

        return [
            'totalAssessments' => $overallStats['total_assessments'],
            'completedAssessments' => $overallStats['graded_assessments'],
            'pendingAssessments' => $overallStats['pending_assessments'],
            'averageScore' => $overallStats['overall_average'],
            'upcomingAssessments' => $this->formatAssignmentsForDashboard($upcomingAssessments),
            'recentAssessments' => $this->formatAssignmentsForDashboard($recentAssessments),
            'subjectsBreakdown' => $overallStats['subjects_breakdown'],
        ];
    }

    /**
     * Get detailed assessment list with normalized grades
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @return array List of assessments with grades on /20 scale
     */
    public function getDetailedAssessmentsList(User $student, ?int $academicYearId = null): array
    {
        return $this->gradeCalculationService->getStudentAssessmentSummary($student, $academicYearId);
    }

    /**
     * Format assignments for dashboard display
     *
     * @param  \Illuminate\Support\Collection  $assignments
     * @return array Formatted assignments
     */
    private function formatAssignmentsForDashboard($assignments): array
    {
        return $assignments->map(function ($assignment) {
            $assessment = $assignment->assessment;
            $maxPoints = $assessment->questions->sum('points');
            $normalizedGrade = null;

            if ($assignment->score !== null && $maxPoints > 0) {
                $normalizedGrade = round(($assignment->score / $maxPoints) * 20, 2);
            }

            return [
                'id' => $assignment->id,
                'assessment_id' => $assignment->assessment_id,
                'title' => $assessment->title ?? '-',
                'subject_name' => $assessment->classSubject->subject->name ?? '-',
                'duration_minutes' => $assessment->duration_minutes,
                'coefficient' => $assessment->coefficient,
                'raw_score' => $assignment->score,
                'max_points' => $maxPoints,
                'normalized_grade' => $normalizedGrade,
                'status' => $assignment->status,
                'submitted_at' => $assignment->submitted_at?->toISOString(),
                'created_at' => $assignment->created_at?->toISOString(),
            ];
        })->toArray();
    }
}
