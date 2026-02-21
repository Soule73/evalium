<?php

namespace App\Contracts\Repositories;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminAssessmentRepositoryInterface
{
    /**
     * Get paginated assessments for a class.
     */
    public function getAssessmentsForClass(ClassModel $class, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get paginated assessments created by a teacher.
     */
    public function getAssessmentsForTeacher(User $teacher, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get paginated assessment assignments for a student.
     */
    public function getAssignmentsForStudent(User $student, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get aggregate assessment statistics for a teacher.
     */
    public function getTeacherAssessmentStats(User $teacher): array;

    /**
     * Get all assessments with optional academic year and filters.
     */
    public function getAllAssessments(?int $academicYearId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
