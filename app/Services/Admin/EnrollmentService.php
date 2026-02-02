<?php

namespace App\Services\Admin;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Enrollment Service - Manage student enrollments in classes
 *
 * Single Responsibility: Handle student enrollment CRUD and status management
 */
class EnrollmentService
{
    /**
     * Enroll a student in a class
     */
    public function enrollStudent(User $student, ClassModel $class): Enrollment
    {
        $this->validateEnrollment($student, $class);

        if ($class->enrollments()->count() >= $class->max_students) {
            throw new InvalidArgumentException('Class is full');
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
    public function transferStudent(Enrollment $enrollment, ClassModel $newClass): Enrollment
    {
        $this->validateEnrollment($enrollment->student, $newClass);

        if ($newClass->enrollments()->count() >= $newClass->max_students) {
            throw new InvalidArgumentException('Target class is full');
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
            throw new InvalidArgumentException('Only withdrawn enrollments can be reactivated');
        }

        if ($enrollment->class->enrollments()->count() >= $enrollment->class->max_students) {
            throw new InvalidArgumentException('Class is full');
        }

        $enrollment->update([
            'status' => 'active',
            'withdrawn_at' => null,
        ]);

        return $enrollment->fresh();
    }

    /**
     * Get active enrollments for a class
     */
    public function getActiveEnrollments(ClassModel $class): Collection
    {
        return Enrollment::active()
            ->where('class_id', $class->id)
            ->with('student')
            ->get();
    }

    /**
     * Get all enrollments for a student
     */
    public function getEnrollmentsForStudent(User $student): Collection
    {
        return Enrollment::where('student_id', $student->id)
            ->with(['class.academicYear', 'class.level'])
            ->orderBy('enrolled_at', 'desc')
            ->get();
    }

    /**
     * Get current active enrollment for a student
     */
    public function getCurrentEnrollment(User $student): ?Enrollment
    {
        return Enrollment::active()
            ->where('student_id', $student->id)
            ->whereHas('class.academicYear', function ($query) {
                $query->where('is_current', true);
            })
            ->with(['class.academicYear', 'class.level'])
            ->first();
    }

    /**
     * Bulk enroll students in a class
     */
    public function bulkEnrollStudents(ClassModel $class, array $studentIds): Collection
    {
        $availableSlots = $class->max_students - $class->enrollments()->count();

        if (count($studentIds) > $availableSlots) {
            throw new InvalidArgumentException("Class has only {$availableSlots} available slots");
        }

        $enrollments = collect();

        DB::transaction(function () use ($class, $studentIds, &$enrollments) {
            foreach ($studentIds as $studentId) {
                $student = User::findOrFail($studentId);
                $enrollments->push($this->enrollStudent($student, $class));
            }
        });

        return $enrollments;
    }

    /**
     * Validate enrollment
     */
    private function validateEnrollment(User $student, ClassModel $class): void
    {
        if (! $student->hasRole('student')) {
            throw new InvalidArgumentException('User must have student role');
        }

        $existingEnrollment = Enrollment::active()
            ->where('student_id', $student->id)
            ->whereHas('class', function ($query) use ($class) {
                $query->where('academic_year_id', $class->academic_year_id);
            })
            ->exists();

        if ($existingEnrollment) {
            throw new InvalidArgumentException('Student already enrolled in a class for this academic year');
        }
    }
}
