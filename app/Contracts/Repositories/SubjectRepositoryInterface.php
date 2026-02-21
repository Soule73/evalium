<?php

namespace App\Contracts\Repositories;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubjectRepositoryInterface
{
    /**
     * Get paginated subjects for the index page.
     */
    public function getSubjectsForIndex(?int $academicYearId, array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all levels from cache.
     */
    public function getAllLevels(): Collection;

    /**
     * Get data required for the create subject form.
     */
    public function getCreateFormData(): array;

    /**
     * Get data required for the edit subject form.
     */
    public function getEditFormData(Subject $subject): array;

    /**
     * Get subject details with paginated class subjects.
     */
    public function getSubjectDetailsWithPagination(Subject $subject, ?int $academicYearId, array $classSubjectsFilters): array;

    /**
     * Get paginated class subjects for a subject.
     */
    public function getPaginatedClassSubjects(Subject $subject, ?int $academicYearId, array $filters): LengthAwarePaginator;
}
