<?php

namespace App\Services\Teacher;

use App\Models\ClassModel;
use Illuminate\Support\Facades\DB;

/**
 * Computes aggregated assessment and student statistics for a teacher's class.
 *
 * Single Responsibility: class-level results synthesis only.
 * Uses raw SQL aggregation to avoid N+1 queries.
 */
class TeacherClassResultsService
{
    /**
     * Returns aggregated results for a class: overview, per-assessment stats, per-student stats.
     *
     * @return array{overview: array, assessment_stats: array, student_stats: array}
     */
    public function getClassResults(ClassModel $class, int $teacherId): array
    {
        $classId = $class->id;

        $totalStudents = (int) DB::table('enrollments')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->count();

        $assessmentStats = $this->computeAssessmentStats($classId, $teacherId, $totalStudents);
        $studentStats = $this->computeStudentStats($classId, $teacherId);

        $overview = $this->buildOverview($totalStudents, $assessmentStats);

        return [
            'overview' => $overview,
            'assessment_stats' => $assessmentStats,
            'student_stats' => $studentStats,
        ];
    }

    /**
     * @return array<int, array{id: int, title: string, type: string, scheduled_at: string|null, subject_name: string, total_assigned: int, graded: int, submitted: int, in_progress: int, not_started: int, average_score: float|null, completion_rate: float}>
     */
    private function computeAssessmentStats(int $classId, int $teacherId, int $totalStudents): array
    {
        $activeEnrollmentSubquery = DB::table('enrollments')
            ->select('id')
            ->where('class_id', $classId)
            ->where('status', 'active');

        $rows = DB::table('assessments as a')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->join('subjects as s', 's.id', '=', 'cs.subject_id')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($activeEnrollmentSubquery) {
                $join->on('aa.assessment_id', '=', 'a.id')
                    ->whereIn('aa.enrollment_id', $activeEnrollmentSubquery);
            })
            ->leftJoin(DB::raw('(SELECT assessment_assignment_id, COALESCE(SUM(score), 0) as total_score FROM answers GROUP BY assessment_assignment_id) as ans_totals'), 'ans_totals.assessment_assignment_id', '=', 'aa.id')
            ->leftJoin(DB::raw('(SELECT assessment_id, COALESCE(SUM(points), 0) as total_points FROM questions GROUP BY assessment_id) as q_totals'), 'q_totals.assessment_id', '=', 'a.id')
            ->where('cs.class_id', $classId)
            ->where('cs.teacher_id', $teacherId)
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.title', 'a.type', 'a.scheduled_at', 's.name', 'q_totals.total_points')
            ->selectRaw('
                a.id,
                a.title,
                a.type,
                a.scheduled_at,
                s.name as subject_name,
                COALESCE(q_totals.total_points, 0) as max_points,
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN aa.submitted_at IS NOT NULL AND aa.graded_at IS NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN aa.started_at IS NOT NULL AND aa.submitted_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                AVG(CASE WHEN aa.graded_at IS NOT NULL THEN ans_totals.total_score ELSE NULL END) as average_raw_score
            ')
            ->orderBy('a.scheduled_at', 'desc')
            ->get();

        return $rows->map(function ($row) use ($totalStudents) {
            $graded = (int) $row->graded;
            $submitted = (int) $row->submitted;
            $inProgress = (int) $row->in_progress;
            $notStarted = max(0, $totalStudents - $graded - $submitted - $inProgress);
            $maxPoints = (float) $row->max_points;

            $averageScore = ($row->average_raw_score !== null && $maxPoints > 0)
                ? round(((float) $row->average_raw_score / $maxPoints) * 20, 2)
                : null;

            return [
                'id' => $row->id,
                'title' => $row->title,
                'type' => $row->type,
                'scheduled_at' => $row->scheduled_at,
                'subject_name' => $row->subject_name,
                'total_assigned' => $totalStudents,
                'graded' => $graded,
                'submitted' => $submitted,
                'in_progress' => $inProgress,
                'not_started' => $notStarted,
                'average_score' => $averageScore,
                'completion_rate' => $totalStudents > 0 ? round(($graded / $totalStudents) * 100, 2) : 0.0,
            ];
        })->toArray();
    }

    /**
     * @return array<int, array{enrollment_id: int, student_name: string, student_email: string, graded_count: int, submitted_count: int, average_score: float|null}>
     *
     * Implements the canonical weighted grade formula in SQL/PHP for performance (avoids N+1).
     * Formula mirrors GradeCalculationService::computeWeightedGrade:
     *   average_score = Σ(coefficient × (raw_score / max_points) × 20) / Σ(coefficient)
     * Only graded assignments (graded_at IS NOT NULL) are included in the weighted average.
     */
    private function computeStudentStats(int $classId, int $teacherId): array
    {
        $assessmentSubquery = DB::table('assessments as a')
            ->select('a.id')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->where('cs.class_id', $classId)
            ->where('cs.teacher_id', $teacherId)
            ->whereNull('a.deleted_at');

        $countRows = DB::table('enrollments as e')
            ->join('users as u', 'u.id', '=', 'e.student_id')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($assessmentSubquery) {
                $join->on('aa.enrollment_id', '=', 'e.id')
                    ->whereIn('aa.assessment_id', $assessmentSubquery);
            })
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->groupBy('e.id', 'u.name', 'u.email')
            ->selectRaw('
                e.id as enrollment_id,
                u.name as student_name,
                u.email as student_email,
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded_count,
                SUM(CASE WHEN aa.submitted_at IS NOT NULL THEN 1 ELSE 0 END) as submitted_count
            ')
            ->orderBy('u.name')
            ->get();

        $gradedRows = DB::table('enrollments as e')
            ->join('assessment_assignments as aa', function ($join) use ($assessmentSubquery) {
                $join->on('aa.enrollment_id', '=', 'e.id')
                    ->whereIn('aa.assessment_id', $assessmentSubquery)
                    ->whereNotNull('aa.graded_at');
            })
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->leftJoin(DB::raw('(SELECT assessment_assignment_id, COALESCE(SUM(score), 0) as total_score FROM answers GROUP BY assessment_assignment_id) as ans_totals'), 'ans_totals.assessment_assignment_id', '=', 'aa.id')
            ->leftJoin(DB::raw('(SELECT assessment_id, COALESCE(SUM(points), 0) as total_points FROM questions GROUP BY assessment_id) as q_totals'), 'q_totals.assessment_id', '=', 'a.id')
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->selectRaw('
                e.id as enrollment_id,
                a.coefficient,
                COALESCE(ans_totals.total_score, 0) as raw_score,
                COALESCE(q_totals.total_points, 0) as max_points
            ')
            ->get()
            ->groupBy('enrollment_id');

        return $countRows->map(function ($row) use ($gradedRows) {
            $gradedEntries = $gradedRows->get($row->enrollment_id, collect());

            $totalWeightedScore = 0.0;
            $totalCoefficients = 0.0;

            foreach ($gradedEntries as $entry) {
                $maxPoints = (float) $entry->max_points;
                if ($maxPoints > 0) {
                    $normalized = ((float) $entry->raw_score / $maxPoints) * 20;
                    $totalWeightedScore += (float) $entry->coefficient * $normalized;
                    $totalCoefficients += (float) $entry->coefficient;
                }
            }

            $averageScore = $totalCoefficients > 0
                ? round($totalWeightedScore / $totalCoefficients, 2)
                : null;

            return [
                'enrollment_id' => $row->enrollment_id,
                'student_name' => $row->student_name,
                'student_email' => $row->student_email,
                'graded_count' => (int) $row->graded_count,
                'submitted_count' => (int) $row->submitted_count,
                'average_score' => $averageScore,
            ];
        })->toArray();
    }

    /**
     * Get chart data for deferred loading on class results page.
     *
     * @return array{scoreDistribution: array, assessmentTrend: array}
     */
    public function getChartData(ClassModel $class, int $teacherId): array
    {
        return [
            'scoreDistribution' => $this->getScoreDistributionForClass($class->id, $teacherId),
            'assessmentTrend' => $this->getAssessmentAverageTrend($class->id, $teacherId),
        ];
    }

    /**
     * Get score distribution across predefined ranges for all class assessments.
     *
     * @return array<int, array{range: string, count: int}>
     */
    public function getScoreDistributionForClass(int $classId, int $teacherId): array
    {
        $scoreExpr = '(SELECT COALESCE(SUM(a_s.score), 0) FROM answers a_s WHERE a_s.assessment_assignment_id = aa.id)'
            .' / (SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id) * 20';
        $maxPointsExpr = '(SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id)';

        $rows = DB::table('assessment_assignments as aa')
            ->join('enrollments as e', 'e.id', '=', 'aa.enrollment_id')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->where('cs.teacher_id', $teacherId)
            ->whereNull('a.deleted_at')
            ->whereNotNull('aa.graded_at')
            ->whereRaw("{$maxPointsExpr} > 0")
            ->selectRaw("
                CASE
                    WHEN ({$scoreExpr}) < 5 THEN '0-4'
                    WHEN ({$scoreExpr}) < 9 THEN '5-8'
                    WHEN ({$scoreExpr}) < 13 THEN '9-12'
                    WHEN ({$scoreExpr}) < 17 THEN '13-16'
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
     * Get average score per assessment over time for line chart.
     *
     * @return array<int, array{name: string, value: float|null}>
     */
    public function getAssessmentAverageTrend(int $classId, int $teacherId): array
    {
        $totalStudents = (int) DB::table('enrollments')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->count();

        $scoreExpr = '(SELECT COALESCE(SUM(a_s.score), 0) FROM answers a_s WHERE a_s.assessment_assignment_id = aa.id)';
        $maxPointsExpr = '(SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id)';

        $activeEnrollmentSubquery = DB::table('enrollments')
            ->select('id')
            ->where('class_id', $classId)
            ->where('status', 'active');

        return DB::table('assessments as a')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($activeEnrollmentSubquery) {
                $join->on('aa.assessment_id', '=', 'a.id')
                    ->whereIn('aa.enrollment_id', $activeEnrollmentSubquery)
                    ->whereNotNull('aa.graded_at');
            })
            ->where('cs.class_id', $classId)
            ->where('cs.teacher_id', $teacherId)
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.title', 'a.scheduled_at')
            ->havingRaw('COUNT(aa.id) > 0')
            ->selectRaw("
                a.title as name,
                ROUND(AVG(
                    CASE WHEN {$maxPointsExpr} > 0
                        THEN {$scoreExpr} / {$maxPointsExpr} * 20
                        ELSE NULL
                    END
                ), 2) as value
            ")
            ->orderBy('a.scheduled_at')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'value' => $row->value !== null ? (float) $row->value : null,
            ])
            ->toArray();
    }

    /**
     * @param  array<int, array>  $assessmentStats
     */
    private function buildOverview(int $totalStudents, array $assessmentStats): array
    {
        $collection = collect($assessmentStats);
        $withScores = $collection->filter(fn ($a) => $a['average_score'] !== null);

        $averageScore = $withScores->isNotEmpty()
            ? round((float) $withScores->avg('average_score'), 2)
            : null;

        $completionRate = $collection->isNotEmpty()
            ? round((float) $collection->avg('completion_rate'), 2)
            : 0.0;

        return [
            'total_students' => $totalStudents,
            'total_assessments' => $collection->count(),
            'average_score' => $averageScore,
            'completion_rate' => $completionRate,
        ];
    }
}
