<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TeacherClassQueryService
{
    /**
     * Get all classes where the teacher is assigned with optimized queries.
     */
    public function getClassesForTeacher(
        int $teacherId,
        int $selectedYearId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->with([
                'class.academicYear',
                'class.level',
                'subject',
            ])
            ->get();

        $classSubjectsByClassId = $classSubjects->groupBy('class_id');

        $classIds = $classSubjectsByClassId->keys();

        $classes = ClassModel::whereIn('id', $classIds)
            ->with(['academicYear', 'level'])
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();

        $classes = $classes->map(function ($class) use ($classSubjectsByClassId) {
            $class->setRelation('class_subjects', $classSubjectsByClassId->get($class->id, collect()));

            return $class;
        });

        $classes = $this->applyClassFilters($classes, $filters);

        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = $classes->count();
        $items = $classes->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get class subjects for a teacher with pagination and filtering.
     */
    public function getSubjectsForClass(
        ClassModel $class,
        int $teacherId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        return ClassSubject::where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->with(['subject'])
            ->withCount('assessments')
            ->when(
                $filters['subjects_search'] ?? null,
                fn ($query, $search) => $query->whereHas(
                    'subject',
                    fn ($q) => $q->where('name', 'like', "%{$search}%")
                )
            )
            ->paginate($perPage, ['*'], 'subjects_page')
            ->withQueryString();
    }

    /**
     * Get assessments for a teacher's class with pagination and filtering.
     */
    public function getAssessmentsForClass(
        ClassModel $class,
        int $teacherId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $classSubjectIds = ClassSubject::where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->pluck('id');

        return Assessment::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->with(['classSubject.subject'])
            ->when(
                $filters['assessments_search'] ?? null,
                fn ($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->latest('scheduled_at')
            ->paginate($perPage, ['*'], 'assessments_page')
            ->withQueryString();
    }

    /**
     * Get students enrolled in a class with pagination and filtering.
     */
    public function getStudentsForClass(
        ClassModel $class,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        return \App\Models\Enrollment::where('class_id', $class->id)
            ->where('status', 'active')
            ->with('student')
            ->when(
                $filters['students_search'] ?? null,
                fn ($query, $search) => $query->whereHas(
                    'student',
                    fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->latest('enrolled_at')
            ->paginate($perPage, ['*'], 'students_page')
            ->withQueryString();
    }

    /**
     * Validate that the class belongs to the selected academic year.
     */
    public function validateAcademicYearAccess(ClassModel $class, int $selectedYearId): void
    {
        if ($class->academic_year_id !== $selectedYearId) {
            throw new \Exception(__('messages.class_not_in_selected_year'));
        }
    }

    /**
     * Apply search and level filters to the classes collection.
     */
    protected function applyClassFilters(Collection $classes, array $filters): Collection
    {
        if ($search = $filters['search'] ?? null) {
            $search = strtolower($search);
            $classes = $classes->filter(function ($class) use ($search) {
                return str_contains(strtolower($class->name ?? ''), $search) ||
                    str_contains(strtolower($class->description ?? ''), $search) ||
                    str_contains(strtolower($class->level->name ?? ''), $search);
            })->values();
        }

        if ($levelId = $filters['level_id'] ?? null) {
            $classes = $classes->where('level_id', $levelId)->values();
        }

        return $classes;
    }
}
