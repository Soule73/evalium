<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
    public function getActiveAssignments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 10): LengthAwarePaginator
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

        return $this->simplePaginate($query, $perPage);
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
    public function getPastAssessments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Assessment::whereHas('classSubject', fn ($q) => $q->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->where('scheduled_at', '<', now())
            ->with(['classSubject.class.level', 'classSubject.class.academicYear', 'classSubject.subject'])
            ->orderBy('scheduled_at', 'desc');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        $assessments = $this->simplePaginate($query, $perPage);

        $assessments->through(fn ($assessment) => $this->formatAssessmentForDisplay($assessment));

        return $assessments;
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
    public function getUpcomingAssessments(int $teacherId, int $academicYearId, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Assessment::whereHas('classSubject', fn ($q) => $q->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->where('scheduled_at', '>=', now())
            ->with(['classSubject.class.level', 'classSubject.class.academicYear', 'classSubject.subject'])
            ->orderBy('scheduled_at', 'asc');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        $assessments = $this->simplePaginate($query, $perPage);

        $assessments->through(fn ($assessment) => $this->formatAssessmentForDisplay($assessment));

        return $assessments;
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
        $totalActiveAssignments = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($academicYearId)
            ->active()
            ->get();

        $totalAssessmentsCount = Assessment::whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
            ->forAcademicYear($academicYearId)
            ->count();

        return [
            'total_classes' => $totalActiveAssignments->unique('class_id')->count(),
            'total_subjects' => $totalActiveAssignments->unique('subject_id')->count(),
            'total_assessments' => $totalAssessmentsCount,
            'past_assessments' => $pastAssessmentsTotal,
            'upcoming_assessments' => $upcomingAssessmentsTotal,
        ];
    }

    /**
     * Format an assessment for display
     *
     * @param  Assessment  $assessment  The assessment
     * @return array Formatted assessment data
     */
    protected function formatAssessmentForDisplay(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'scheduled_at' => $assessment->scheduled_at,
            'classSubject' => [
                'class' => [
                    'name' => $assessment->classSubject?->class?->name,
                    'display_name' => $assessment->classSubject?->class?->display_name,
                    'level' => $assessment->classSubject?->class?->level ? ['name' => $assessment->classSubject->class->level->name] : null,
                    'academic_year' => $assessment->classSubject?->class?->academicYear ? ['name' => $assessment->classSubject->class->academicYear->name] : null,
                ],
                'subject' => [
                    'name' => $assessment->classSubject?->subject?->name,
                    'code' => $assessment->classSubject?->subject?->code,
                ],
            ],
        ];
    }
}
