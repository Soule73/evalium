<?php

namespace App\Contracts\Repositories;

use App\Models\ClassSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClassSubjectRepositoryInterface
{
    /**
     * Get paginated class subjects for the index page.
     */
    public function getClassSubjectsForIndex(?int $selectedYearId, array $filters, bool $activeOnly, int $perPage): LengthAwarePaginator;

    /**
     * Load detailed relationships for a class subject.
     */
    public function loadClassSubjectDetails(ClassSubject $classSubject): ClassSubject;

    /**
     * Get class and subject models for the history page.
     */
    public function getClassAndSubjectForHistory(int $classId, int $subjectId): array;

    /**
     * Get teachers available for replacement assignment.
     */
    public function getTeachersForReplacement(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get paginated teaching history for a class-subject pair.
     */
    public function getPaginatedHistory(int $classId, int $subjectId, int $perPage = 10, ?int $excludeId = null): LengthAwarePaginator;
}
