<?php

namespace App\Contracts\Repositories;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeacherSubjectRepositoryInterface
{
    /**
     * Get paginated subjects for a teacher with filters.
     */
    public function getSubjectsForTeacher(int $teacherId, int $selectedYearId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get classes for filter dropdown.
     */
    public function getClassesForFilter(int $teacherId, int $selectedYearId): Collection;

    /**
     * Authorize that the teacher has access to this subject.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function authorizeTeacherSubject(int $teacherId, int $subjectId, int $selectedYearId): void;

    /**
     * Get subject details with classes info.
     */
    public function getSubjectDetails(Subject $subject, int $teacherId, int $selectedYearId): Subject;

    /**
     * Get assessments for a subject taught by this teacher.
     */
    public function getAssessmentsForSubject(Subject $subject, int $teacherId, int $selectedYearId, array $filters, int $perPage): LengthAwarePaginator;
}
