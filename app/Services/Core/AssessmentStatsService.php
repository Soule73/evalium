<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\DB;

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

    private const STUDENT_SCORE_SQL = '(SELECT COALESCE(SUM(a_s.score), 0) FROM answers a_s WHERE a_s.assessment_assignment_id = aa.id)';

    private const MAX_POINTS_SQL = '(SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = aa.assessment_id)';

    private const NORMALIZED_SCORE_SQL = self::STUDENT_SCORE_SQL.' / '.self::MAX_POINTS_SQL.' * 20';

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
     * Get score distribution across predefined ranges for a single assessment.
     *
     * @param  int  $assessmentId  The assessment ID
     * @return array<int, array{range: string, count: int}>
     */
    public function getScoreDistribution(int $assessmentId): array
    {
        $classId = DB::table('assessments')
            ->join('class_subjects', 'class_subjects.id', '=', 'assessments.class_subject_id')
            ->where('assessments.id', $assessmentId)
            ->value('class_subjects.class_id');

        $score = self::NORMALIZED_SCORE_SQL;

        $rows = DB::table('assessment_assignments as aa')
            ->join('enrollments as e', 'e.id', '=', 'aa.enrollment_id')
            ->where('aa.assessment_id', $assessmentId)
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->whereNotNull('aa.graded_at')
            ->whereRaw(self::MAX_POINTS_SQL.' > 0')
            ->selectRaw("
                CASE
                    WHEN ({$score}) < 5 THEN '0-4'
                    WHEN ({$score}) < 9 THEN '5-8'
                    WHEN ({$score}) < 13 THEN '9-12'
                    WHEN ({$score}) < 17 THEN '13-16'
                    ELSE '17-20'
                END as `range`,
                COUNT(*) as count
            ")
            ->groupBy('range')
            ->get();

        $ranges = ['0-4', '5-8', '9-12', '13-16', '17-20'];
        $result = [];
        foreach ($ranges as $range) {
            $found = $rows->firstWhere('range', $range);
            $result[] = ['range' => $range, 'count' => (int) ($found->count ?? 0)];
        }

        return $result;
    }

    /**
     * Get assignment status breakdown formatted for donut chart.
     *
     * @param  int  $assessmentId  The assessment ID
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function getAssessmentStatusChart(int $assessmentId): array
    {
        $stats = $this->calculateAssessmentStats($assessmentId);

        return [
            ['name' => __('charts.completion.graded'), 'value' => $stats['graded'], 'color' => '#10b981'],
            ['name' => __('charts.completion.submitted'), 'value' => $stats['submitted'], 'color' => '#3b82f6'],
            ['name' => __('charts.completion.in_progress'), 'value' => $stats['in_progress'], 'color' => '#f59e0b'],
            ['name' => __('charts.completion.not_started'), 'value' => $stats['not_started'], 'color' => '#6b7280'],
        ];
    }

    /**
     * Compute assessment stats by starting from active enrollments (source of truth).
     *
     * AssessmentAssignment is created lazily when a student first opens the assessment,
     * so querying it directly would always return 0 for "not started" students.
     * Instead, we LEFT JOIN assignments onto enrollments to capture all cases:
     * - enrolled but no assignment record → not started
     * - assignment exists but started_at IS NULL → not started
     * - assignment exists with started_at only → in progress
     * - assignment submitted but not graded → submitted
     * - assignment graded → graded
     *
     * NOTE: average_score is intentionally returned as raw points (not /20) because
     * this service is used for a single-assessment view where the denominator is
     * displayed alongside as totalPoints (e.g. "8.5 / 15").
     * Normalization to /20 is the responsibility of GradeCalculationService.
     */
    private function computeAssessmentStats(int $assessmentId): array
    {
        $classId = DB::table('assessments')
            ->join('class_subjects', 'class_subjects.id', '=', 'assessments.class_subject_id')
            ->where('assessments.id', $assessmentId)
            ->value('class_subjects.class_id');

        $row = DB::table('enrollments as e')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($assessmentId) {
                $join->on('aa.enrollment_id', '=', 'e.id')
                    ->where('aa.assessment_id', '=', $assessmentId);
            })
            ->leftJoin(DB::raw('(SELECT assessment_assignment_id, COALESCE(SUM(score), 0) as total_score FROM answers GROUP BY assessment_assignment_id) as ans_totals'), 'ans_totals.assessment_assignment_id', '=', 'aa.id')
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->selectRaw('
                COUNT(*) as total_assigned,
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN aa.submitted_at IS NOT NULL AND aa.graded_at IS NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN aa.started_at IS NOT NULL AND aa.submitted_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN aa.id IS NULL OR aa.started_at IS NULL THEN 1 ELSE 0 END) as not_started,
                AVG(CASE WHEN aa.graded_at IS NOT NULL THEN ans_totals.total_score ELSE NULL END) as average_score
            ')
            ->first();

        $totalAssigned = (int) ($row->total_assigned ?? 0);
        $graded = (int) ($row->graded ?? 0);
        $inProgress = (int) ($row->in_progress ?? 0);
        $notStarted = (int) ($row->not_started ?? 0);
        $averageScore = $row->average_score !== null ? round((float) $row->average_score, 2) : null;

        return [
            'total_assigned' => $totalAssigned,
            'graded' => $graded,
            'submitted' => (int) ($row->submitted ?? 0),
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
