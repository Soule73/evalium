<?php

namespace App\Contracts\Repositories;

use App\Models\Enrollment;

interface EnrollmentRepositoryInterface
{
    /**
     * Get paginated enrollments for the index page.
     */
    public function getEnrollmentsForIndex(?int $academicYearId, array $filters, int $perPage = 15): array;

    /**
     * Get data required for the create enrollment form.
     */
    public function getCreateFormData(?int $academicYearId): array;

    /**
     * Get data required for the show enrollment page.
     */
    public function getShowData(Enrollment $enrollment, ?int $academicYearId): array;

    /**
     * Get class subjects available for an enrollment.
     */
    public function getClassSubjectsForEnrollment(Enrollment $enrollment): array;
}
