<?php

namespace App\Contracts\Repositories;

use App\Models\ClassModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeacherClassRepositoryInterface
{
    /**
     * Get paginated classes for a teacher with filters.
     */
    public function getClassesForTeacher(int $teacherId, int $selectedYearId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get paginated class subjects for a teacher's class.
     */
    public function getSubjectsForClass(ClassModel $class, int $teacherId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get paginated assessments for a teacher's class.
     */
    public function getAssessmentsForClass(ClassModel $class, int $teacherId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get paginated students enrolled in a class.
     */
    public function getStudentsForClass(ClassModel $class, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get distinct active classes for a teacher using a JOIN query.
     */
    public function getActiveClassesForTeacher(int $teacherId, ?int $selectedYearId): Collection;

    /**
     * Get subject filter data for a teacher's class (for filter dropdowns).
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getSubjectFilterDataForClass(ClassModel $class, int $teacherId): Collection;

    /**
     * Validate that the class belongs to the selected academic year.
     *
     * @throws \Exception
     */
    public function validateAcademicYearAccess(ClassModel $class, int $selectedYearId): void;
}
