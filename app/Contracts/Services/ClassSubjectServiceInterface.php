<?php

namespace App\Contracts\Services;

use App\Models\ClassSubject;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ClassSubjectServiceInterface
{
    /**
     * Get data required for the create class-subject form.
     */
    public function getFormDataForCreate(int $selectedYearId): array;

    /**
     * Assign a teacher to a class subject.
     */
    public function assignTeacherToClassSubject(array $data): ClassSubject;

    /**
     * Replace current teacher with a new one.
     */
    public function replaceTeacher(ClassSubject $classSubject, int $newTeacherId, ?Carbon $replacementDate): ClassSubject;

    /**
     * Get teaching history for a class-subject pair.
     */
    public function getTeachingHistory(int $classId, int $subjectId): Collection;

    /**
     * Update the coefficient for a class subject.
     */
    public function updateCoefficient(ClassSubject $classSubject, float $coefficient): ClassSubject;

    /**
     * Terminate an active class-subject assignment.
     */
    public function terminateAssignment(ClassSubject $classSubject, ?Carbon $endDate = null): ClassSubject;

    /**
     * Get all class subjects for an academic year.
     */
    public function getClassSubjectsForAcademicYear(int $academicYearId, bool $activeOnly = true): Collection;

    /**
     * Delete class subject, throwing if assessments are linked.
     */
    public function deleteClassSubject(ClassSubject $classSubject): void;
}
