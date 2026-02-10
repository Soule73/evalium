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
        private readonly GradeCalculationService $gradeCalculationService
    ) {}

    /**
     * Get comprehensive dashboard statistics for a student
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  \App\Models\Enrollment|null  $enrollment  Pre-loaded enrollment to avoid duplicate query
     * @return array{totalAssessments: int, completedAssessments: int, pendingAssessments: int, averageScore: float|null}
     */
    public function getDashboardStats(User $student, ?int $academicYearId = null, $enrollment = null): array
    {
        $overallStats = $this->gradeCalculationService->getStudentOverallStats($student, $academicYearId, $enrollment);

        return [
            'totalAssessments' => $overallStats['total_assessments'],
            'completedAssessments' => $overallStats['graded_assessments'],
            'pendingAssessments' => $overallStats['pending_assessments'],
            'averageScore' => $overallStats['overall_average'],
        ];
    }
}
