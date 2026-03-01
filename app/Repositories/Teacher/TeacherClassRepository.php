<?php

namespace App\Repositories\Teacher;

use App\Contracts\Repositories\TeacherClassRepositoryInterface;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TeacherClassRepository implements TeacherClassRepositoryInterface
{
    /**
     * Get paginated classes where the teacher is assigned using SQL-level filtering.
     */
    public function getClassesForTeacher(
        int $teacherId,
        int $selectedYearId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $query = ClassModel::query()
            ->whereHas('classSubjects', function ($q) use ($teacherId, $selectedYearId) {
                $q->where('teacher_id', $teacherId)
                    ->whereNull('valid_to')
                    ->whereHas('class', fn ($cq) => $cq->where('academic_year_id', $selectedYearId));
            })
            ->with([
                'level',
                'classSubjects' => function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId)
                        ->whereNull('valid_to')
                        ->with('subject');
                },
            ])
            ->withCount(['enrollments as active_enrollments_count' => function ($q) {
                $q->where('status', 'active');
            }]);

        if ($search = $filters['search'] ?? null) {
            $like = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereHas('level', fn ($lq) => $lq->whereRaw('LOWER(name) LIKE ?', [$like]));
            });
        }

        if ($levelId = $filters['level_id'] ?? null) {
            $query->where('level_id', $levelId);
        }

        return $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
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
        return Assessment::query()
            ->whereIn('class_subject_id', function ($query) use ($class, $teacherId) {
                $query->select('id')
                    ->from('class_subjects')
                    ->where('class_id', $class->id)
                    ->where('teacher_id', $teacherId);
            })
            ->with(['classSubject'])
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
     * Get distinct active classes for a teacher using an efficient JOIN query.
     *
     * @return Collection<int, object{class_id: int, class_name: string, level_name: string, level_description: string}>
     */
    public function getActiveClassesForTeacher(int $teacherId, ?int $selectedYearId): Collection
    {
        return ClassSubject::query()
            ->select([
                'classes.id as class_id',
                'classes.name as class_name',
                'levels.name as level_name',
                'levels.description as level_description',
            ])
            ->join('classes', 'classes.id', '=', 'class_subjects.class_id')
            ->join('levels', 'levels.id', '=', 'classes.level_id')
            ->where('class_subjects.teacher_id', $teacherId)
            ->whereNull('class_subjects.valid_to')
            ->when($selectedYearId, fn ($q) => $q->where('classes.academic_year_id', $selectedYearId))
            ->distinct()
            ->get();
    }

    /**
     * Validate that the class belongs to the selected academic year.
     */
    public function validateAcademicYearAccess(ClassModel $class, int $selectedYearId): void
    {
        if ($class->academic_year_id !== $selectedYearId) {
            abort(403, __('messages.class_not_in_selected_year'));
        }
    }

    /**
     * Get subject filter data for a teacher's class (used for filter dropdowns).
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getSubjectFilterDataForClass(ClassModel $class, int $teacherId): Collection
    {
        return ClassSubject::query()
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->whereNull('valid_to')
            ->with('subject:id,name')
            ->get()
            ->map(fn ($cs) => ['id' => $cs->subject_id, 'name' => $cs->subject?->name])
            ->filter(fn ($s) => $s['name'] !== null)
            ->values();
    }
}
