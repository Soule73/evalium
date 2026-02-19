<?php

namespace App\Repositories\Teacher;

use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Contracts\Repositories\TeacherClassRepositoryInterface;
use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TeacherAssessmentRepository implements TeacherAssessmentRepositoryInterface
{
    public function __construct(
        private readonly TeacherClassRepositoryInterface $classQueryService
    ) {}

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
            ->with(['classSubject.class.level', 'classSubject.subject'])
            ->withCount('questions')
            ->forAcademicYear($selectedYearId)
            ->whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))
            ->when(
                $filters['search'] ?? null,
                fn($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->when(
                $filters['class_subject_id'] ?? null,
                fn($query, $classSubjectId) => $query->where('class_subject_id', $classSubjectId)
            )
            ->when(
                $filters['class_id'] ?? null,
                fn($query, $classId) => $query->whereHas('classSubject', fn($q) => $q->where('class_id', $classId))
            )
            ->when(
                $filters['type'] ?? null,
                fn($query, $type) => $query->where('type', $type)
            )
            ->when(
                isset($filters['is_published']),
                fn($query) => $query->where('is_published', (bool) $filters['is_published'])
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
        $defaultRelations = ['class.level', 'subject'];
        $relations = array_unique(array_merge($defaultRelations, $withRelations));

        return ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->with($relations)
            ->active()
            ->get();
    }

    /**
     * Get distinct classes for the filter dropdown using a single JOIN query.
     *
     * @return Collection<int, object{class_id: int, class_name: string, level_name: string, level_description: string}>
     */
    public function getClassFilterDataForTeacher(int $teacherId, int $selectedYearId): Collection
    {
        return $this->classQueryService->getActiveClassesForTeacher($teacherId, $selectedYearId);
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
            'assignments.enrollment.student',
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
