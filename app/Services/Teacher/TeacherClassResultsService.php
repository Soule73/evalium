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
            ->where('cs.class_id', $classId)
            ->where('cs.teacher_id', $teacherId)
            ->whereNull('a.deleted_at')
            ->groupBy('a.id', 'a.title', 'a.type', 'a.scheduled_at', 's.name')
            ->selectRaw('
                a.id,
                a.title,
                a.type,
                a.scheduled_at,
                s.name as subject_name,
                (SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id) as max_points,
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN aa.submitted_at IS NOT NULL AND aa.graded_at IS NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN aa.started_at IS NOT NULL AND aa.submitted_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                AVG(CASE WHEN aa.graded_at IS NOT NULL THEN (SELECT COALESCE(SUM(ans.score), 0) FROM answers ans WHERE ans.assessment_assignment_id = aa.id) ELSE NULL END) as average_raw_score
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
            ->where('e.class_id', $classId)
            ->where('e.status', 'active')
            ->selectRaw('
                e.id as enrollment_id,
                a.coefficient,
                (SELECT COALESCE(SUM(ans.score), 0) FROM answers ans WHERE ans.assessment_assignment_id = aa.id) as raw_score,
                (SELECT COALESCE(SUM(q.points), 0) FROM questions q WHERE q.assessment_id = a.id) as max_points
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
