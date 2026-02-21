<?php

namespace App\Contracts\Services;

use App\Models\Enrollment;
use App\Models\User;

interface EnrollmentServiceInterface
{
    /**
     * Enroll a student in a class.
     */
    public function enrollStudent(int $studentId, int $classId): Enrollment;

    /**
     * Transfer a student to a different class.
     */
    public function transferStudent(Enrollment $enrollment, int $newClassId): Enrollment;

    /**
     * Withdraw a student from their current class.
     */
    public function withdrawStudent(Enrollment $enrollment): Enrollment;

    /**
     * Reactivate a withdrawn enrollment.
     */
    public function reactivateEnrollment(Enrollment $enrollment): Enrollment;

    /**
     * Get the current enrollment for a student.
     */
    public function getCurrentEnrollment(User $student, ?int $academicYearId = null): ?Enrollment;
}
