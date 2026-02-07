<?php

namespace App\Services\Admin;

use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassSubjectQueryService
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
            ->with(['class.academicYear', 'class.level', 'subject', 'teacher', 'semester'])
            ->when(
                $filters['class_id'] ?? null,
                fn($query, $classId) => $query->where('class_id', $classId)
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn($query, $subjectId) => $query->where('subject_id', $subjectId)
            )
            ->when(
                $filters['teacher_id'] ?? null,
                fn($query, $teacherId) => $query->where('teacher_id', $teacherId)
            )
            ->when($activeOnly, fn($query) => $query->active())
            ->orderBy('valid_from', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get form data for filtering (index page).
     */
    public function getFormDataForIndex(int $selectedYearId): array
    {
        return [
            'classes' => ClassModel::forAcademicYear($selectedYearId)
                ->with('academicYear')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => User::role('teacher')->orderBy('name')->get(),
        ];
    }

    /**
     * Get form data for creation/editing.
     */
    public function getFormDataForCreate(int $selectedYearId): array
    {
        return [
            'classes' => ClassModel::forAcademicYear($selectedYearId)
                ->with('academicYear', 'level')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::with('level')->orderBy('name')->get(),
            'teachers' => User::role('teacher')->orderBy('name')->get(),
            'semesters' => Semester::where('academic_year_id', $selectedYearId)
                ->with('academicYear')
                ->orderBy('order_number')
                ->get(),
        ];
    }

    /**
     * Load detailed relationships for a class subject.
     */
    public function loadClassSubjectDetails(ClassSubject $classSubject): ClassSubject
    {
        return $classSubject->load([
            'class.academicYear',
            'class.level',
            'subject',
            'teacher',
            'semester',
            'assessments',
        ]);
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
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->with(['teacher', 'semester'])
            ->orderByDesc('valid_from')
            ->paginate($perPage, ['*'], 'history_page')
            ->withQueryString();
    }
}
