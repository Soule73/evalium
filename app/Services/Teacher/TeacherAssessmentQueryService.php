<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TeacherAssessmentQueryService
{
    /**
     * Get assessments for a teacher with filters and pagination.
     */
    public function getAssessmentsForTeacher(
        int $teacherId,
        int $selectedYearId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        return Assessment::query()
            ->with(['classSubject.class', 'classSubject.subject', 'questions'])
            ->forAcademicYear($selectedYearId)
            ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->when(
                $filters['class_subject_id'] ?? null,
                fn ($query, $classSubjectId) => $query->where('class_subject_id', $classSubjectId)
            )
            ->when(
                $filters['type'] ?? null,
                fn ($query, $type) => $query->where('type', $type)
            )
            ->when(
                isset($filters['is_published']),
                fn ($query) => $query->where('is_published', (bool) $filters['is_published'])
            )
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get active class subjects for a teacher.
     */
    public function getClassSubjectsForTeacher(
        int $teacherId,
        int $selectedYearId,
        array $withRelations = []
    ): Collection {
        $defaultRelations = ['class', 'subject'];
        $relations = array_merge($defaultRelations, $withRelations);

        return ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->with($relations)
            ->active()
            ->get();
    }

    /**
     * Load detailed assessment relationships for display.
     */
    public function loadAssessmentDetails(Assessment $assessment): Assessment
    {
        return $assessment->load([
            'classSubject.class.academicYear',
            'classSubject.class.level',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
            'assignments.student',
        ]);
    }

    /**
     * Load assessment relationships for editing.
     */
    public function loadAssessmentForEdit(Assessment $assessment): Assessment
    {
        return $assessment->load([
            'classSubject.class',
            'classSubject.subject',
            'questions.choices',
        ]);
    }
}
