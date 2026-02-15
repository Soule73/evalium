<?php

namespace App\Services\Admin;

use App\Exceptions\EnrollmentException;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Traits\Paginatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Enrollment Service - Manage student enrollments in classes
 *
 * Single Responsibility: Handle student enrollment CRUD and status management
 * Performance: Optimized queries with proper eager loading
 */
class EnrollmentService
{
    use Paginatable;

    /**
     * Get paginated enrollments for index page
     */
    public function getEnrollmentsForIndex(?int $academicYearId, array $filters, int $perPage = 15): array
    {
        $query = Enrollment::query()
            ->forAcademicYear($academicYearId)
            ->with(['student', 'class.academicYear', 'class.level'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['class_id'] ?? null, fn($query, $classId) => $query->where('class_id', $classId))
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->orderBy('enrolled_at', 'desc');

        $enrollments = $this->paginateQuery($query, $perPage);

        $classes = ClassModel::forAcademicYear($academicYearId)
            ->with('academicYear')
            ->orderBy('name')
            ->get();

        return [
            'enrollments' => $enrollments,
            'filters' => $filters,
            'classes' => $classes,
        ];
    }

    /**
     * Get form data for create page (optimized)
     */
    public function getCreateFormData(?int $academicYearId): array
    {
        $classes = ClassModel::forAcademicYear($academicYearId)
            ->with(['academicYear', 'level', 'enrollments' => fn($q) => $q->where('status', 'active')->with('student:id,name,email,avatar')])
            ->withCount([
                'enrollments as active_enrollments_count' => fn($q) => $q->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get();

        $students = User::role('student')
            ->select(['id', 'name', 'email', 'avatar'])
            ->orderBy('name')
            ->get();

        return [
            'classes' => $classes,
            'students' => $students,
        ];
    }

    /**
     * Get data for show page with classes for transfer modal
     */
    public function getShowData(Enrollment $enrollment, ?int $academicYearId): array
    {
        $enrollment->load(['student', 'class.academicYear', 'class.level']);

        $classes = ClassModel::forAcademicYear($academicYearId)
            ->with(['level', 'academicYear'])
            ->withCount([
                'enrollments as active_enrollments_count' => fn($q) => $q->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get();

        return [
            'enrollment' => $enrollment,
            'classes' => $classes,
        ];
    }

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
     * Bulk enroll students in a class
     */
    public function bulkEnrollStudents(ClassModel $class, array $studentIds): Collection
    {
        $availableSlots = $class->max_students - $class->enrollments()->count();

        if (count($studentIds) > $availableSlots) {
            throw EnrollmentException::classFull($availableSlots);
        }

        $enrollments = collect();

        DB::transaction(function () use ($class, $studentIds, &$enrollments) {
            foreach ($studentIds as $studentId) {
                $enrollments->push($this->enrollStudent($studentId, $class->id));
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

    /**
     * Get class subjects list for an enrollment's class (used as filter options).
     *
     * @return array<int, array{id: int, subject_name: string, teacher_name: string}>
     */
    public function getClassSubjectsForEnrollment(Enrollment $enrollment): array
    {
        return ClassSubject::active()
            ->where('class_id', $enrollment->class_id)
            ->with(['subject:id,name', 'teacher:id,name'])
            ->get()
            ->map(fn(ClassSubject $cs) => [
                'id' => $cs->id,
                'subject_name' => $cs->subject?->name ?? '-',
                'teacher_name' => $cs->teacher?->name ?? '-',
            ])
            ->all();
    }
}
