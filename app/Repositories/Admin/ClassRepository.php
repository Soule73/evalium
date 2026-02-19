<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Models\ClassModel;
use App\Models\Level;
use App\Services\Core\CacheService;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class Query Service - Handle class queries, filtering, and pagination
 *
 * Handles all read operations for classes, separated from CRUD operations.
 * This follows Single Responsibility Principle by focusing only on querying.
 * Performance: Uses cache for frequently accessed, rarely modified data.
 */
class ClassRepository implements ClassRepositoryInterface
{
    use Paginatable;

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get paginated classes for index page with filters
     */
    public function getClassesForIndex(int $academicYearId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ClassModel::query()
            ->forAcademicYear($academicYearId)
            ->with(['level'])
            ->withCount([
                'enrollments as active_enrollments_count' => function ($query) {
                    $query->where('status', 'active');
                },
                'classSubjects as subjects_count',
            ])
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['level_id'] ?? null, fn($query, $levelId) => $query->where('level_id', $levelId))
            ->orderBy('level_id')
            ->orderBy('name');

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get all levels for dropdown (cached)
     */
    public function getAllLevels(): Collection
    {
        return $this->cacheService->remember(
            CacheService::KEY_LEVELS_ALL,
            fn() => Level::orderBy('name')->get()
        );
    }

    /**
     * Invalidate levels cache (called when levels are modified)
     */
    public function invalidateLevelsCache(): void
    {
        $this->cacheService->forget(CacheService::KEY_LEVELS_ALL);
    }

    /**
     * Get class details with paginated students and subjects
     */
    public function getClassDetailsWithPagination(
        ClassModel $class,
        array $studentsFilters,
        array $subjectsFilters
    ): array {
        $class->load(['academicYear', 'level']);
        $class->can_delete = $class->canBeDeleted();

        $enrollments = $this->getPaginatedEnrollments($class, $studentsFilters);
        $classSubjects = $this->getPaginatedClassSubjects($class, $subjectsFilters);

        return [
            'class' => $class,
            'enrollments' => $enrollments,
            'classSubjects' => $classSubjects,
            'statistics' => $this->getClassStatistics($class),
            'studentsFilters' => [
                'search' => $studentsFilters['search'],
            ],
            'subjectsFilters' => [
                'search' => $subjectsFilters['search'],
            ],
        ];
    }

    /**
     * Get paginated enrollments for a class
     */
    public function getPaginatedEnrollments(ClassModel $class, array $filters): LengthAwarePaginator
    {
        $query = $class->enrollments()
            ->with('student')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('enrolled_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $filters['per_page'] ?? 10, 'page' => $filters['page'] ?? 1],
            ['search' => $filters['search'] ?? null]
        );
    }

    /**
     * Get paginated class subjects for a class
     */
    public function getPaginatedClassSubjects(ClassModel $class, array $filters): LengthAwarePaginator
    {
        $query = $class->classSubjects()
            ->with(['subject', 'teacher', 'semester'])
            ->withCount('assessments')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('subject', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $filters['per_page'] ?? 10, 'page' => $filters['page'] ?? 1],
            ['search' => $filters['search'] ?? null]
        );
    }

    /**
     * Get class statistics
     */
    public function getClassStatistics(ClassModel $class): array
    {
        return [
            'total_students' => $class->enrollments()->count(),
            'active_students' => $class->enrollments()->where('status', 'active')->count(),
            'max_students' => $class->max_students,
            'available_slots' => $class->max_students - $class->enrollments()->count(),
            'subjects_count' => $class->classSubjects()->active()->count(),
            'assessments_count' => $class->classSubjects()
                ->active()
                ->withCount('assessments')
                ->get()
                ->sum('assessments_count'),
        ];
    }
}
