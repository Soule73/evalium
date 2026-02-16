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
     * Get active class-subject assignments for a teacher
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated active assignments
     */
    public function getActiveAssignments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 5): LengthAwarePaginator
    {
        $query = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($academicYearId)
            ->active()
            ->with(['class.level', 'class.academicYear', 'subject']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('class', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get past assessments for a teacher
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated past assessments
     */
    public function getPastAssessments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 5): LengthAwarePaginator
    {
        $query = Assessment::whereHas('classSubject', fn ($q) => $q->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->where('scheduled_at', '<', now())
            ->with(['classSubject.class.level', 'classSubject.subject', 'classSubject.teacher'])
            ->orderBy('scheduled_at', 'desc');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get upcoming assessments for a teacher
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  string|null  $search  Optional search query
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated upcoming assessments
     */
    public function getUpcomingAssessments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 5): LengthAwarePaginator
    {
        $query = Assessment::whereHas('classSubject', fn ($q) => $q->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->where('scheduled_at', '>=', now())
            ->with(['classSubject.class.level', 'classSubject.subject', 'classSubject.teacher'])
            ->orderBy('scheduled_at', 'asc');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get dashboard statistics for a teacher
     *
     * @param  int  $teacherId  The teacher ID
     * @param  int  $academicYearId  The academic year ID
     * @param  int  $pastAssessmentsTotal  Total past assessments count
     * @param  int  $upcomingAssessmentsTotal  Total upcoming assessments count
     * @return array Statistics
     */
    public function getDashboardStats(int $teacherId, int $academicYearId, int $pastAssessmentsTotal, int $upcomingAssessmentsTotal): array
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
                ->whereHas('class', fn ($q) => $q->where('academic_year_id', $academicYearId));
        })->count();

        return [
            'total_classes' => (int) ($stats->total_classes ?? 0),
            'total_subjects' => (int) ($stats->total_subjects ?? 0),
            'total_assessments' => $totalAssessmentsCount,
            'past_assessments' => $pastAssessmentsTotal,
            'upcoming_assessments' => $upcomingAssessmentsTotal,
        ];
    }
}
