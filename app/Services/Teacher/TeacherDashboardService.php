<?php

namespace App\Services\Teacher;

use App\Models\ClassSubject;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Dashboard Service
 *
 * Handles business logic for teacher dashboard data preparation.
 * Single Responsibility: Prepare teacher dashboard data only.
 */
class TeacherDashboardService
{
    use Paginatable;

    private const STUDENT_SCORE_SQL = '(SELECT COALESCE(SUM(a.score), 0) FROM answers a WHERE a.assessment_assignment_id = assessment_assignments.id)';

    private const MAX_POINTS_SQL = '(SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = assessments.id)';

    private const NORMALIZED_SCORE_SQL = self::STUDENT_SCORE_SQL.' / '.self::MAX_POINTS_SQL.' * 20';

    /**
     * Get active class-subject assignments for a teacher.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated active assignments
     */
    public function getActiveAssignments(int $teacherId, ?int $academicYearId, ?string $search = null, int $perPage = 3): LengthAwarePaginator
    {
        $query = ClassSubject::where('teacher_id', $teacherId)
            ->when($academicYearId, fn ($q) => $q->forAcademicYear($academicYearId))
            ->active()
            ->with(['class.level', 'subject']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('class', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get dashboard statistics for a teacher using a single optimized query.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  The academic year ID
     * @return array{total_classes: int, total_subjects: int, total_assessments: int, in_progress_assessments: int}
     */
    public function getDashboardStats(int $teacherId, ?int $academicYearId): array
    {
        $row = DB::selectOne('
            SELECT
                (SELECT COUNT(DISTINCT cs.class_id)
                    FROM class_subjects cs
                    INNER JOIN classes c ON cs.class_id = c.id
                    WHERE cs.teacher_id = ? AND cs.valid_to IS NULL
                    '.($academicYearId ? 'AND c.academic_year_id = ?' : '').'
                ) as total_classes,
                (SELECT COUNT(DISTINCT cs.subject_id)
                    FROM class_subjects cs
                    INNER JOIN classes c ON cs.class_id = c.id
                    WHERE cs.teacher_id = ? AND cs.valid_to IS NULL
                    '.($academicYearId ? 'AND c.academic_year_id = ?' : '').'
                ) as total_subjects,
                (SELECT COUNT(*)
                    FROM assessments a
                    INNER JOIN class_subjects cs ON a.class_subject_id = cs.id
                    INNER JOIN classes c ON cs.class_id = c.id
                    WHERE cs.teacher_id = ? AND a.deleted_at IS NULL
                    '.($academicYearId ? 'AND c.academic_year_id = ?' : '').'
                ) as total_assessments,
                (SELECT COUNT(DISTINCT a.id)
                    FROM assessments a
                    INNER JOIN class_subjects cs ON a.class_subject_id = cs.id
                    INNER JOIN classes c ON cs.class_id = c.id
                    INNER JOIN assessment_assignments aa ON a.id = aa.assessment_id
                    WHERE cs.teacher_id = ? AND a.deleted_at IS NULL
                        AND aa.started_at IS NOT NULL AND aa.submitted_at IS NULL
                    '.($academicYearId ? 'AND c.academic_year_id = ?' : '').'
                ) as in_progress_assessments
        ', $this->buildRepeatedBindings($teacherId, $academicYearId, 4));

        return [
            'total_classes' => (int) ($row->total_classes ?? 0),
            'total_subjects' => (int) ($row->total_subjects ?? 0),
            'total_assessments' => (int) ($row->total_assessments ?? 0),
            'in_progress_assessments' => (int) ($row->in_progress_assessments ?? 0),
        ];
    }

    /**
     * Get overall average score across all teacher's graded assessments.
     *
     * Normalizes each assignment score to /20 using:
     * score = SUM(answers.score), max_points = SUM(questions.points)
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return float|null The overall average /20, null if no graded assignments
     */
    public function getOverallAverageScore(int $teacherId, ?int $academicYearId): ?float
    {
        $avg = $this->baseGradedQuery($teacherId, $academicYearId)
            ->selectRaw('AVG('.self::NORMALIZED_SCORE_SQL.') as avg_score')
            ->value('avg_score');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Get assessment completion overview aggregated across all teacher's assessments.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return array{graded: int, submitted: int, in_progress: int, not_started: int}
     */
    public function getAssessmentCompletionOverview(int $teacherId, ?int $academicYearId): array
    {
        $row = $this->baseQuery($teacherId, $academicYearId)
            ->selectRaw('
                SUM(CASE WHEN assessment_assignments.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN assessment_assignments.submitted_at IS NOT NULL AND assessment_assignments.graded_at IS NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN assessment_assignments.started_at IS NOT NULL AND assessment_assignments.submitted_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN assessment_assignments.started_at IS NULL THEN 1 ELSE 0 END) as not_started
            ')
            ->first();

        return [
            'graded' => (int) ($row->graded ?? 0),
            'submitted' => (int) ($row->submitted ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'not_started' => (int) ($row->not_started ?? 0),
        ];
    }

    /**
     * Get score distribution across predefined ranges for histogram chart.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return array<int, array{range: string, count: int}>
     */
    public function getScoreDistribution(int $teacherId, ?int $academicYearId): array
    {
        $score = self::NORMALIZED_SCORE_SQL;

        $rows = $this->baseGradedQuery($teacherId, $academicYearId)
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
     * Get average score per class for bar chart.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return Collection<int, object{name: string, value: float|null}>
     */
    public function getClassPerformanceChart(int $teacherId, ?int $academicYearId): Collection
    {
        $nameExpr = $this->concatColumns('classes.name', "' ('", 'levels.name', "')'");

        $scoreExpr = 'CASE WHEN COUNT(assessment_assignments.id) > 0 THEN ROUND(AVG('.self::NORMALIZED_SCORE_SQL.'), 1) ELSE NULL END';

        return DB::table('class_subjects')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.id')
            ->join('levels', 'classes.level_id', '=', 'levels.id')
            ->leftJoin('assessments', 'assessments.class_subject_id', '=', 'class_subjects.id')
            ->leftJoin('assessment_assignments', function ($join) {
                $join->on('assessment_assignments.assessment_id', '=', 'assessments.id')
                    ->whereNotNull('assessment_assignments.graded_at')
                    ->whereRaw(self::MAX_POINTS_SQL.' > 0');
            })
            ->where('class_subjects.teacher_id', $teacherId)
            ->when($academicYearId, fn ($q) => $q->where('classes.academic_year_id', $academicYearId))
            ->groupBy('classes.id', 'classes.name', 'levels.name')
            ->orderBy('classes.name')
            ->select(
                DB::raw("{$nameExpr} as name"),
                DB::raw("{$scoreExpr} as value")
            )
            ->get();
    }

    /**
     * Get all chart data for the teacher dashboard.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return array Chart datasets for frontend rendering
     */
    public function getChartData(int $teacherId, ?int $academicYearId): array
    {
        return [
            'completionOverview' => $this->getAssessmentCompletionOverview($teacherId, $academicYearId),
            'scoreDistribution' => $this->getScoreDistribution($teacherId, $academicYearId),
            'classPerformance' => $this->getClassPerformanceChart($teacherId, $academicYearId),
        ];
    }

    /**
     * Build base query with common joins for assignment-related queries.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     */
    private function baseQuery(int $teacherId, ?int $academicYearId): \Illuminate\Database\Query\Builder
    {
        return DB::table('assessment_assignments')
            ->join('assessments', 'assessment_assignments.assessment_id', '=', 'assessments.id')
            ->join('class_subjects', 'assessments.class_subject_id', '=', 'class_subjects.id')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.id')
            ->where('class_subjects.teacher_id', $teacherId)
            ->when($academicYearId, fn ($q) => $q->where('classes.academic_year_id', $academicYearId));
    }

    /**
     * Build base query filtered for graded assignments with valid max points.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     */
    private function baseGradedQuery(int $teacherId, ?int $academicYearId): \Illuminate\Database\Query\Builder
    {
        return $this->baseQuery($teacherId, $academicYearId)
            ->whereNotNull('assessment_assignments.graded_at')
            ->whereRaw(self::MAX_POINTS_SQL.' > 0');
    }

    /**
     * Build repeated parameter bindings array for raw SQL with multiple subselects.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int|null  $academicYearId  Optional academic year filter
     * @param  int  $count  Number of times to repeat the binding set
     * @return array<int, int>
     */
    private function buildRepeatedBindings(int $teacherId, ?int $academicYearId, int $count): array
    {
        $unit = $academicYearId !== null ? [$teacherId, $academicYearId] : [$teacherId];
        $bindings = [];
        for ($i = 0; $i < $count; $i++) {
            $bindings = array_merge($bindings, $unit);
        }

        return $bindings;
    }

    /**
     * Build a database-agnostic SQL concatenation expression.
     *
     * Uses CONCAT() for MySQL and || for SQLite.
     *
     * @param  string  ...$parts  Column names or quoted literals to concatenate
     */
    private function concatColumns(string ...$parts): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return implode(' || ', $parts);
        }

        return 'CONCAT('.implode(', ', $parts).')';
    }
}
