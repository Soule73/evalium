<?php

namespace App\Contracts\Repositories;

use App\Models\ClassModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ClassRepositoryInterface
{
    /**
     * Get paginated classes for the index page.
     */
    public function getClassesForIndex(?int $academicYearId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get all levels from cache.
     */
    public function getAllLevels(): Collection;

    /**
     * Invalidate the levels cache.
     */
    public function invalidateLevelsCache(): void;

    /**
     * Get paginated enrollments for a class.
     */
    public function getPaginatedEnrollments(ClassModel $class, array $filters): LengthAwarePaginator;

    /**
     * Get paginated class subjects for a class.
     */
    public function getPaginatedClassSubjects(ClassModel $class, array $filters): LengthAwarePaginator;

    /**
     * Get aggregate statistics for a class.
     */
    public function getClassStatistics(ClassModel $class, ?int $subjectsCount = null): array;
}
