<?php

namespace App\Services\Core;

use App\Models\AssessmentAssignment;

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
}
