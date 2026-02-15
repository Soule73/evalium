<?php

namespace App\Services\Core;

use App\Models\AssessmentAssignment;
use App\Models\User;

/**
 * Assessment Statistics Service
 *
 * Handles statistics calculations for assessments and student performance.
 * Single Responsibility: Calculate assessment-related statistics only.
 * Performance: Uses cache for expensive calculations.
 */
class AssessmentStatsService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Calculate comprehensive student progress metrics (cached)
     */
    public function calculateStudentProgress(User $student): array
    {
        return $this->cacheService->remember(
            $this->cacheService->studentProgressKey($student->id),
            fn () => $this->computeStudentProgress($student),
            self::CACHE_TTL
        );
    }

    /**
     * Compute student progress (uncached)
     */
    private function computeStudentProgress(User $student): array
    {
        $assignments = AssessmentAssignment::forStudent($student)
            ->with('assessment')
            ->get();

        $totalAssessments = $assignments->count();
        $completedAssessments = $assignments->whereNotNull('submitted_at')->count();

        $gradedAssignments = $assignments->whereNotNull('score');
        $averageScore = $gradedAssignments->isEmpty()
            ? null
            : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assessments' => $totalAssessments,
            'completed_assessments' => $completedAssessments,
            'average_score' => $averageScore,
            'pending_assessments' => $totalAssessments - $completedAssessments,
        ];
    }

    /**
     * Calculate assessment statistics for a specific assessment (cached)
     */
    public function calculateAssessmentStats(int $assessmentId): array
    {
        return $this->cacheService->remember(
            $this->cacheService->assessmentStatsKey($assessmentId),
            fn () => $this->computeAssessmentStats($assessmentId),
            self::CACHE_TTL
        );
    }

    /**
     * Compute assessment stats (uncached)
     */
    private function computeAssessmentStats(int $assessmentId): array
    {
        $assignments = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->select(['id', 'assessment_id', 'started_at', 'submitted_at', 'graded_at', 'score'])
            ->get();

        $totalAssigned = $assignments->count();
        $graded = $assignments->whereNotNull('graded_at')->count();
        $submitted = $assignments->whereNotNull('submitted_at')->whereNull('graded_at')->count();
        $inProgress = $assignments->whereNotNull('started_at')->whereNull('submitted_at')->count();
        $notStarted = $assignments->whereNull('started_at')->count();

        $gradedAssignments = $assignments->whereNotNull('score');
        $averageScore = $gradedAssignments->isEmpty()
            ? null
            : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assigned' => $totalAssigned,
            'graded' => $graded,
            'submitted' => $submitted,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'not_submitted' => $inProgress + $notStarted,
            'average_score' => $averageScore,
            'completion_rate' => $totalAssigned > 0
                ? round(($graded / $totalAssigned) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * Calculate class-level assessment statistics
     *
     * @param  int  $classId  The class ID
     * @return array Class assessment statistics
     */
    public function calculateClassStats(int $classId): array
    {
        $assignments = AssessmentAssignment::whereHas('student.classes', function ($query) use ($classId) {
            $query->where('classes.id', $classId);
        })->with('assessment')->get();

        $totalAssessments = $assignments->count();
        $completed = $assignments->whereNotNull('submitted_at')->count();
        $gradedAssignments = $assignments->whereNotNull('score');

        $averageScore = $gradedAssignments->isEmpty()
            ? null
            : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assessments' => $totalAssessments,
            'completed' => $completed,
            'average_score' => $averageScore,
            'completion_rate' => $totalAssessments > 0
                ? round(($completed / $totalAssessments) * 100, 2)
                : 0.0,
        ];
    }
}
