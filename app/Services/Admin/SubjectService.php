<?php

namespace App\Services\Admin;

use App\Models\Level;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubjectService
{
    /**
     * Get paginated subjects with filters
     */
    public function getSubjectsForIndex(?int $academicYearId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Subject::query()
            ->with(['level', 'classSubjects' => function ($query) use ($academicYearId) {
                if ($academicYearId) {
                    $query->whereHas('class', function ($q) use ($academicYearId) {
                        $q->where('academic_year_id', $academicYearId);
                    });
                }
            }])
            ->withCount(['classSubjects' => function ($query) use ($academicYearId) {
                if ($academicYearId) {
                    $query->whereHas('class', function ($q) use ($academicYearId) {
                        $q->where('academic_year_id', $academicYearId);
                    });
                }
            }])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when($filters['level_id'] ?? null, fn ($query, $levelId) => $query->where('level_id', $levelId))
            ->orderBy('level_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all levels for dropdown
     */
    public function getAllLevels(): Collection
    {
        return Level::orderBy('name')->get();
    }

    /**
     * Get form data for create page
     */
    public function getCreateFormData(): array
    {
        return [
            'levels' => $this->getAllLevels(),
        ];
    }

    /**
     * Get form data for edit page
     */
    public function getEditFormData(Subject $subject): array
    {
        return [
            'subject' => $subject->load('level'),
            'levels' => $this->getAllLevels(),
        ];
    }

    /**
     * Get subject details with paginated class subjects
     */
    public function getSubjectDetailsWithPagination(
        Subject $subject,
        ?int $academicYearId,
        array $classSubjectsFilters
    ): array {
        $subject->load('level');

        $classSubjects = $this->getPaginatedClassSubjects($subject, $academicYearId, $classSubjectsFilters);

        return [
            'subject' => $subject,
            'classSubjects' => $classSubjects,
            'classSubjectsFilters' => [
                'search' => $classSubjectsFilters['search'],
            ],
        ];
    }

    /**
     * Get paginated class subjects for a subject
     */
    public function getPaginatedClassSubjects(Subject $subject, ?int $academicYearId, array $filters)
    {
        return $subject->classSubjects()
            ->with(['class.academicYear', 'class.level', 'teacher'])
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->whereHas('class', function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                });
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('class', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('teacher', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(
                $filters['per_page'] ?? 10,
                ['*'],
                'classes_page',
                $filters['page'] ?? 1
            )
            ->withQueryString()
            ->appends([
                'classes_search' => $filters['search'],
                'classes_per_page' => $filters['per_page'],
            ]);
    }

    /**
     * Create a new subject
     */
    public function createSubject(array $validatedData): Subject
    {
        return Subject::create($validatedData);
    }

    /**
     * Update a subject
     */
    public function updateSubject(Subject $subject, array $validatedData): Subject
    {
        $subject->update($validatedData);

        return $subject;
    }

    /**
     * Delete a subject
     */
    public function deleteSubject(Subject $subject): bool
    {
        return $subject->delete();
    }

    /**
     * Check if subject has class subjects
     */
    public function hasClassSubjects(Subject $subject): bool
    {
        return $subject->classSubjects()->exists();
    }
}
