<?php

namespace App\Services\Student;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\Core\GradeCalculationService;
use Illuminate\Support\Facades\DB;

/**
 * Student Dashboard Service
 *
 * Orchestrates dashboard data by delegating calculations to GradeCalculationService.
 * Provides chart data methods for the student dashboard using optimized SQL queries.
 *
 * Single Responsibility: Format and organize dashboard data for presentation
 */
class StudentDashboardService
{
    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService
    ) {}

    /**
     * Get comprehensive dashboard statistics for a student.
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  Enrollment|null  $enrollment  Pre-loaded enrollment to avoid duplicate query
     * @return array{totalAssessments: int, completedAssessments: int, pendingAssessments: int, averageScore: float|null}
     */
    public function getDashboardStats(User $student, ?int $academicYearId = null, ?Enrollment $enrollment = null): array
    {
        $overallStats = $this->gradeCalculationService->getStudentOverallStats($student, $academicYearId, $enrollment);

        return [
            'totalAssessments' => $overallStats['total_assessments'],
            'completedAssessments' => $overallStats['graded_assessments'],
            'pendingAssessments' => $overallStats['pending_assessments'],
            'averageScore' => $overallStats['overall_average'],
        ];
    }

    /**
     * Get subject grades with class averages for radar chart.
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  Enrollment|null  $enrollment  Pre-loaded enrollment
     * @return array<int, array{subject: string, grade: float, classAverage: float|null}>
     */
    public function getSubjectRadarData(User $student, ?int $academicYearId, ?Enrollment $enrollment = null): array
    {
        $enrollment = $this->resolveEnrollment($student, $academicYearId, $enrollment);

        if (! $enrollment) {
            return [];
        }

        $enrollment->loadMissing('class');
        $breakdown = $this->gradeCalculationService->getGradeBreakdown($student, $enrollment->class);

        if (empty($breakdown['subjects'])) {
            return [];
        }

        $classSubjectIds = collect($breakdown['subjects'])->pluck('class_subject_id')->toArray();
        $classAverages = $this->computeClassAverages($classSubjectIds);

        return collect($breakdown['subjects'])
            ->map(fn ($s) => [
                'subject' => $s['subject_name'],
                'grade' => $s['average'],
                'classAverage' => isset($classAverages[$s['class_subject_id']])
                    ? round((float) $classAverages[$s['class_subject_id']], 2)
                    : null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get assessment status breakdown for donut chart.
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  Enrollment|null  $enrollment  Pre-loaded enrollment
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function getAssessmentStatusChart(User $student, ?int $academicYearId, ?Enrollment $enrollment = null): array
    {
        $enrollment = $this->resolveEnrollment($student, $academicYearId, $enrollment);

        if (! $enrollment) {
            return [];
        }

        $stats = DB::table('assessments as a')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($enrollment) {
                $join->on('aa.assessment_id', '=', 'a.id')
                    ->where('aa.enrollment_id', '=', $enrollment->id);
            })
            ->where('cs.class_id', $enrollment->class_id)
            ->whereNull('cs.valid_to')
            ->where('a.is_published', true)
            ->whereNull('a.deleted_at')
            ->selectRaw('
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN aa.submitted_at IS NOT NULL AND aa.graded_at IS NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN aa.started_at IS NOT NULL AND aa.submitted_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN aa.started_at IS NULL OR aa.id IS NULL THEN 1 ELSE 0 END) as not_started
            ')
            ->first();

        return [
            ['name' => __('charts.completion.graded'), 'value' => (int) ($stats->graded ?? 0), 'color' => '#10b981'],
            ['name' => __('charts.completion.submitted'), 'value' => (int) ($stats->submitted ?? 0), 'color' => '#3b82f6'],
            ['name' => __('charts.completion.in_progress'), 'value' => (int) ($stats->in_progress ?? 0), 'color' => '#f59e0b'],
            ['name' => __('charts.completion.not_started'), 'value' => (int) ($stats->not_started ?? 0), 'color' => '#6b7280'],
        ];
    }

    /**
     * Get recent graded assessment scores for bar chart.
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  int  $limit  Maximum number of assessments to return
     * @return array<int, array{name: string, value: float|null}>
     */
    public function getRecentScoresChart(User $student, ?int $academicYearId, int $limit = 10): array
    {
        $query = DB::table('assessment_assignments as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->join('enrollments as e', 'e.id', '=', 'aa.enrollment_id')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id');

        if ($academicYearId) {
            $query->join('classes as c', 'c.id', '=', 'cs.class_id')
                ->where('c.academic_year_id', $academicYearId);
        }

        return $query
            ->where('e.student_id', $student->id)
            ->where('a.is_published', true)
            ->whereNull('a.deleted_at')
            ->whereNotNull('aa.graded_at')
            ->selectRaw('
                a.title as name,
                ROUND(
                    (SELECT COALESCE(SUM(ans.score), 0) FROM answers ans WHERE ans.assessment_assignment_id = aa.id)
                    / NULLIF((SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id), 0)
                    * 20,
                    2
                ) as value
            ')
            ->orderByDesc('aa.graded_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'value' => $row->value !== null ? (float) $row->value : null,
            ])
            ->toArray();
    }

    /**
     * Get grade trend over time for line chart (monthly averages).
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @return array<int, array{name: string, value: float|null}>
     */
    public function getGradeTrend(User $student, ?int $academicYearId): array
    {
        $query = DB::table('assessment_assignments as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->join('enrollments as e', 'e.id', '=', 'aa.enrollment_id')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id');

        if ($academicYearId) {
            $query->join('classes as c', 'c.id', '=', 'cs.class_id')
                ->where('c.academic_year_id', $academicYearId);
        }

        $monthExpr = $this->monthExpression('aa.graded_at');

        return $query
            ->where('e.student_id', $student->id)
            ->where('a.is_published', true)
            ->whereNull('a.deleted_at')
            ->whereNotNull('aa.graded_at')
            ->groupByRaw($monthExpr)
            ->selectRaw("
                {$monthExpr} as name,
                ROUND(AVG(
                    (SELECT COALESCE(SUM(ans.score), 0) FROM answers ans WHERE ans.assessment_assignment_id = aa.id)
                    / NULLIF((SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id), 0)
                    * 20
                ), 2) as value
            ")
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'value' => $row->value !== null ? (float) $row->value : null,
            ])
            ->toArray();
    }

    /**
     * Get all chart data for deferred loading.
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Academic year filter
     * @param  Enrollment|null  $enrollment  Pre-loaded enrollment
     * @return array{subjectRadar: array, assessmentStatus: array, recentScores: array, gradeTrend: array}
     */
    public function getChartData(User $student, ?int $academicYearId, ?Enrollment $enrollment = null): array
    {
        return [
            'subjectRadar' => $this->getSubjectRadarData($student, $academicYearId, $enrollment),
            'assessmentStatus' => $this->getAssessmentStatusChart($student, $academicYearId, $enrollment),
            'recentScores' => $this->getRecentScoresChart($student, $academicYearId),
            'gradeTrend' => $this->getGradeTrend($student, $academicYearId),
        ];
    }

    /**
     * Resolve an enrollment for a student.
     */
    private function resolveEnrollment(User $student, ?int $academicYearId, ?Enrollment $enrollment): ?Enrollment
    {
        if ($enrollment) {
            return $enrollment;
        }

        $query = $student->enrollments()->where('status', 'active');

        if ($academicYearId) {
            $query->whereHas('class', fn ($q) => $q->where('academic_year_id', $academicYearId));
        }

        return $query->with('class')->first();
    }

    /**
     * Compute class averages per subject via SQL.
     *
     * @param  array<int>  $classSubjectIds  Class subject IDs
     * @return array<int, float> Keyed by class_subject_id
     */
    private function computeClassAverages(array $classSubjectIds): array
    {
        if (empty($classSubjectIds)) {
            return [];
        }

        return DB::table('assessments as a')
            ->join('assessment_assignments as aa', function ($join) {
                $join->on('aa.assessment_id', '=', 'a.id')
                    ->whereNotNull('aa.graded_at');
            })
            ->whereIn('a.class_subject_id', $classSubjectIds)
            ->where('a.is_published', true)
            ->whereNull('a.deleted_at')
            ->groupBy('a.class_subject_id')
            ->selectRaw('
                a.class_subject_id,
                ROUND(AVG(
                    (SELECT COALESCE(SUM(ans.score), 0) FROM answers ans WHERE ans.assessment_assignment_id = aa.id)
                    / NULLIF((SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id), 0)
                    * 20
                ), 2) as class_average
            ')
            ->pluck('class_average', 'class_subject_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    /**
     * Get database-specific month formatting expression.
     */
    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
