<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Services\Traits\Paginatable;

/**
 * Enrollment Query Service - Handle all read operations for enrollments.
 *
 * Follows Single Responsibility Principle by separating query concerns
 * from business logic in EnrollmentService.
 */
class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    use Paginatable;

    /**
     * Get paginated enrollments for index page with filters.
     */
    public function getEnrollmentsForIndex(?int $academicYearId, array $filters, int $perPage = 15): array
    {
        $query = Enrollment::query()
            ->forAcademicYear($academicYearId)
            ->with(['student', 'class.level'])
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
            ->orderBy('name')
            ->get();

        return [
            'enrollments' => $enrollments,
            'filters' => $filters,
            'classes' => $classes,
        ];
    }

    /**
     * Get form data for create page.
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

        $students = \App\Models\User::role('student')
            ->select(['id', 'name', 'email', 'avatar'])
            ->orderBy('name')
            ->get();

        return [
            'classes' => $classes,
            'students' => $students,
        ];
    }

    /**
     * Get data for show page with classes for transfer modal.
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
