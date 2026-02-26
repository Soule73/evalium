<?php

namespace App\Contracts\Repositories;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Contract for student-facing enrollment read operations.
 *
 * Separates query concerns from business logic and enables
 * testability through dependency injection.
 */
interface StudentEnrollmentRepositoryInterface
{
    /**
     * Get all subjects with grade statistics for a student's enrollment (non-paginated).
     *
     * @param  Enrollment  $enrollment  The student's active enrollment
     * @param  User  $student  The student user
     * @param  array  $filters  Optional search filters
     * @return Collection<int, \App\Models\ClassSubject>
     */
    public function getAllSubjectsWithStats(Enrollment $enrollment, User $student, array $filters = []): Collection;

    /**
     * Get paginated subjects with grade statistics for a student's enrollment.
     *
     * @param  Enrollment  $enrollment  The student's active enrollment
     * @param  User  $student  The student user
     * @param  array  $filters  Optional search filters
     * @param  int  $perPage  Items per page
     */
    public function getSubjectsWithStatsForEnrollment(
        Enrollment $enrollment,
        User $student,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator;

    /**
     * Get enrollment history for a student across academic years.
     *
     * @param  User  $student  The student user
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return Collection<int, Enrollment>
     */
    public function getEnrollmentHistory(User $student, ?int $academicYearId): Collection;

    /**
     * Get active classmates for a student's enrollment.
     *
     * @param  Enrollment  $enrollment  The student's enrollment
     * @param  User  $student  The student to exclude from results
     * @return Collection<int, User>
     */
    public function getClassmates(Enrollment $enrollment, User $student): Collection;

    /**
     * Validate that an enrollment belongs to the selected academic year.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function validateAcademicYearAccess(Enrollment $enrollment, int $selectedYearId): bool;
}
