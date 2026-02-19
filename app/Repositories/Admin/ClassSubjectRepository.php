<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\ClassSubjectRepositoryInterface;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassSubjectRepository implements ClassSubjectRepositoryInterface
{
    /**
     * Get class subjects with filters and pagination.
     */
    public function getClassSubjectsForIndex(
        int $selectedYearId,
        array $filters,
        bool $activeOnly,
        int $perPage
    ): LengthAwarePaginator {
        return ClassSubject::query()
            ->forAcademicYear($selectedYearId)
            ->with(['class.level', 'subject', 'teacher', 'semester'])
            ->withCount('assessments')
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(function ($q) use ($search) {
                    $q->whereHas('class', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                })
            )
            ->when(
                $filters['class_id'] ?? null,
                fn ($query, $classId) => $query->where('class_id', $classId)
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn ($query, $subjectId) => $query->where('subject_id', $subjectId)
            )
            ->when(
                $filters['teacher_id'] ?? null,
                fn ($query, $teacherId) => $query->where('teacher_id', $teacherId)
            )
            ->when($activeOnly, fn ($query) => $query->active())
            ->orderBy('valid_from', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Load detailed relationships for a class subject.
     */
    public function loadClassSubjectDetails(ClassSubject $classSubject): ClassSubject
    {
        return $classSubject->loadMissing([
            'class.academicYear',
            'class.level',
            'subject',
            'teacher',
            'semester',
        ])->loadCount('assessments');
    }

    /**
     * Get class and subject for history page.
     */
    public function getClassAndSubjectForHistory(int $classId, int $subjectId): array
    {
        return [
            'class' => ClassModel::with('academicYear', 'level')->findOrFail($classId),
            'subject' => Subject::findOrFail($subjectId),
        ];
    }

    /**
     * Get list of teachers available for replacement.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getTeachersForReplacement(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role('teacher')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get paginated teaching history for a class-subject combination.
     */
    public function getPaginatedHistory(int $classId, int $subjectId, int $perPage = 10, ?int $excludeId = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ClassSubject::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->with(['teacher', 'semester'])
            ->orderByDesc('valid_from')
            ->paginate($perPage, ['*'], 'history_page')
            ->withQueryString();
    }
}
