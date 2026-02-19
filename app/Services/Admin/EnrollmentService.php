<?php

namespace App\Services\Admin;

use App\Contracts\Services\EnrollmentServiceInterface;
use App\Exceptions\EnrollmentException;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Enrollment Service - Manage student enrollments in classes
 *
 * Single Responsibility: Handle student enrollment CRUD and status management
 * Performance: Optimized queries with proper eager loading
 */
class EnrollmentService implements EnrollmentServiceInterface
{
    /**
     * Enroll a student in a class
     */
    public function enrollStudent(int $studentId, int $classId): Enrollment
    {
        $student = User::findOrFail($studentId);
        $class = ClassModel::findOrFail($classId);

        $this->validateEnrollment($student, $class);

        if ($class->enrollments()->count() >= $class->max_students) {
            throw EnrollmentException::classFull();
        }

        return Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Transfer a student from one class to another
     */
    public function transferStudent(Enrollment $enrollment, int $newClassId): Enrollment
    {
        $newClass = ClassModel::findOrFail($newClassId);

        $this->validateEnrollment($enrollment->student, $newClass);

        if ($newClass->enrollments()->count() >= $newClass->max_students) {
            throw EnrollmentException::targetClassFull();
        }

        return DB::transaction(function () use ($enrollment, $newClass) {
            $enrollment->update([
                'status' => 'withdrawn',
                'withdrawn_at' => now(),
            ]);

            return Enrollment::create([
                'class_id' => $newClass->id,
                'student_id' => $enrollment->student_id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
        });
    }

    /**
     * Withdraw a student from a class
     */
    public function withdrawStudent(Enrollment $enrollment): Enrollment
    {
        $enrollment->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        return $enrollment->fresh();
    }

    /**
     * Reactivate a withdrawn enrollment
     */
    public function reactivateEnrollment(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'withdrawn') {
            throw EnrollmentException::invalidStatus($enrollment->status);
        }

        if ($enrollment->class->enrollments()->count() >= $enrollment->class->max_students) {
            throw EnrollmentException::classFull();
        }

        $enrollment->update([
            'status' => 'active',
            'withdrawn_at' => null,
        ]);

        return $enrollment->fresh();
    }

    /**
     * Get current active enrollment for a student
     */
    public function getCurrentEnrollment(User $student, ?int $academicYearId = null): ?Enrollment
    {
        $query = Enrollment::active()
            ->where('student_id', $student->id);

        if ($academicYearId) {
            $query->forAcademicYear($academicYearId);
        } else {
            $query->whereHas('class.academicYear', function ($q) {
                $q->where('is_current', true);
            });
        }

        return $query->with(['class.academicYear', 'class.level'])->first();
    }

    /**
     * Validate enrollment
     */
    private function validateEnrollment(User $student, ClassModel $class): void
    {
        if (! $student->hasRole('student')) {
            throw EnrollmentException::invalidStudentRole();
        }

        $existingEnrollment = Enrollment::active()
            ->where('student_id', $student->id)
            ->whereHas('class', function ($query) use ($class) {
                $query->where('academic_year_id', $class->academic_year_id);
            })
            ->exists();

        if ($existingEnrollment) {
            throw EnrollmentException::alreadyEnrolled();
        }
    }
}
