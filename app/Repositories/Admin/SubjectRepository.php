<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\SubjectRepositoryInterface;
use App\Models\Level;
use App\Models\Subject;
use App\Services\Core\CacheService;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Subject Query Service - Handle all read operations for subjects.
 *
 * Follows Single Responsibility Principle by separating query concerns
 * from business logic in SubjectService.
 * Performance: Uses cache for frequently accessed data.
 */
class SubjectRepository implements SubjectRepositoryInterface
{
    use Paginatable;

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get paginated subjects with filters.
     */
    public function getSubjectsForIndex(?int $academicYearId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Subject::query()
            ->with(['level'])
            ->withCount(['classSubjects' => function ($query) use ($academicYearId) {
                if ($academicYearId) {
                    $query->whereHas('class', function ($q) use ($academicYearId) {
                        $q->where('academic_year_id', $academicYearId);
                    });
                }
            }])
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when($filters['level_id'] ?? null, fn($query, $levelId) => $query->where('level_id', $levelId))
            ->orderBy('level_id')
            ->orderBy('name');

        /** @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Subject> $paginated */
        $paginated = $this->paginateQuery($query, $perPage);

        $paginated->getCollection()->transform(function ($subject) {
            $subject->can_delete = $subject->canBeDeleted();

            return $subject;
        });

        return $paginated;
    }

    /**
     * Get all levels for dropdown (cached).
     */
    public function getAllLevels(): Collection
    {
        return $this->cacheService->remember(
            CacheService::KEY_LEVELS_ALL,
            fn() => Level::orderBy('name')->get()
        );
    }

    /**
     * Get form data for create page.
     */
    public function getCreateFormData(): array
    {
        return [
            'levels' => $this->getAllLevels(),
        ];
    }

    /**
     * Get form data for edit page.
     */
    public function getEditFormData(Subject $subject): array
    {
        return [
            'subject' => $subject->load('level'),
            'levels' => $this->getAllLevels(),
        ];
    }

    /**
     * Get subject details with paginated class subjects.
     */
    public function getSubjectDetailsWithPagination(
        Subject $subject,
        ?int $academicYearId,
        array $classSubjectsFilters
    ): array {
        $subject->load('level');
        $subject->can_delete = $subject->canBeDeleted();

        $classSubjects = $this->getPaginatedClassSubjects($subject, $academicYearId, $classSubjectsFilters);

        return [
            'subject' => $subject,
            'classSubjects' => $classSubjects,
        ];
    }

    /**
     * Get paginated class subjects for a subject.
     */
    public function getPaginatedClassSubjects(Subject $subject, ?int $academicYearId, array $filters): LengthAwarePaginator
    {
        $query = $subject->classSubjects()
            ->with(['class.academicYear', 'class.level', 'teacher'])
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->whereHas('class', function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                });
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('class', function ($classQuery) use ($search) {
                        $classQuery->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                            $teacherQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $filters['per_page'] ?? 10, 'page' => $filters['page'] ?? 1],
            ['search' => $filters['search'] ?? null]
        );
    }
}
