<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * Get active class-subject assignments for a teacher.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated active assignments
     */
    public function getActiveAssignments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 3): LengthAwarePaginator
    {
        $query = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($academicYearId)
            ->active()
            ->with(['class.level', 'subject']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('class', fn($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn($query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get the most recent assessments for a teacher.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $limit  Maximum number of assessments to return
     * @return LengthAwarePaginator Paginated recent assessments
     */
    public function getRecentAssessments(int $teacherId, int $academicYearId, ?string $search = null, int $limit = 3): LengthAwarePaginator
    {
        $query = Assessment::whereHas('classSubject', fn($q) => $q->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->with(['classSubject'])
            ->orderBy('scheduled_at', 'desc');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $this->paginateQuery($query, $limit);
    }

    /**
     * Get dashboard statistics for a teacher.
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @return array{total_classes: int, total_subjects: int, total_assessments: int, in_progress_assessments: int} Statistics
     */
    public function getDashboardStats(int $teacherId, int $academicYearId): array
    {
        $stats = DB::table('class_subjects')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.id')
            ->where('class_subjects.teacher_id', $teacherId)
            ->where('classes.academic_year_id', $academicYearId)
            ->whereNull('class_subjects.valid_to')
            ->selectRaw('
                COUNT(DISTINCT class_subjects.class_id) as total_classes,
                COUNT(DISTINCT class_subjects.subject_id) as total_subjects
            ')
            ->first();

        $totalAssessmentsCount = Assessment::whereHas('classSubject', function ($query) use ($teacherId, $academicYearId) {
            $query->where('teacher_id', $teacherId)
                ->whereHas('class', fn($q) => $q->where('academic_year_id', $academicYearId));
        })->count();

        $inProgressCount = Assessment::whereHas('classSubject', function ($query) use ($teacherId, $academicYearId) {
            $query->where('teacher_id', $teacherId)
                ->whereHas('class', fn($q) => $q->where('academic_year_id', $academicYearId));
        })->whereHas('assignments', fn($q) => $q->inProgress())->count();

        return [
            'total_classes' => (int) ($stats->total_classes ?? 0),
            'total_subjects' => (int) ($stats->total_subjects ?? 0),
            'total_assessments' => $totalAssessmentsCount,
            'in_progress_assessments' => $inProgressCount,
        ];
    }
}
