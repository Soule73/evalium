<?php

namespace App\Contracts\Repositories;

use App\Models\Assessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeacherAssessmentRepositoryInterface
{
    /**
     * Get paginated assessments for a teacher with filters.
     */
    public function getAssessmentsForTeacher(int $teacherId, ?int $selectedYearId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get active class subjects for a teacher.
     */
    public function getClassSubjectsForTeacher(int $teacherId, ?int $selectedYearId, array $withRelations = []): Collection;

    /**
     * Get distinct classes for the filter dropdown.
     */
    public function getClassFilterDataForTeacher(int $teacherId, ?int $selectedYearId): Collection;

    /**
     * Load detailed assessment relationships for display.
     */
    public function loadAssessmentDetails(Assessment $assessment): Assessment;

    /**
     * Load assessment relationships for editing.
     */
    public function loadAssessmentForEdit(Assessment $assessment): Assessment;
}
