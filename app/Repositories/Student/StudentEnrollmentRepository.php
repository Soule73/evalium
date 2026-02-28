<?php

namespace App\Repositories\Student;

use App\Contracts\Repositories\StudentEnrollmentRepositoryInterface;
use App\Enums\EnrollmentStatus;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Student Enrollment Query Service
 *
 * Handles all read operations for student enrollments.
 * Single Responsibility: Query student enrollment data only.
 */
class StudentEnrollmentRepository implements StudentEnrollmentRepositoryInterface
{
    /**
     * Get all subjects with stats for a student's enrollment (non-paginated).
     *
     * Loads all class subjects with eager-loaded relationships in a single batch.
     * Preferred when total subject count per class is small (typically <20).
     *
     * @param  Enrollment  $enrollment  The student's enrollment
     * @param  User  $student  The student user
     * @param  array  $filters  Search filters
     * @return Collection<int, ClassSubject> All subjects with stats
     */
    public function getAllSubjectsWithStats(
        Enrollment $enrollment,
        User $student,
        array $filters = []
    ): Collection {
        return ClassSubject::active()
            ->where('class_id', $enrollment->class_id)
            ->with([
                'subject',
                'teacher',
                'assessments' => function ($query) use ($enrollment) {
                    $query->select('id', 'class_subject_id', 'coefficient', 'settings')
                        ->with([
                            'questions:id,assessment_id,points',
                            'assignments' => function ($q) use ($enrollment) {
                                $q->where('enrollment_id', $enrollment->id)
                                    ->select('id', 'assessment_id', 'enrollment_id', 'submitted_at', 'graded_at')
                                    ->withSum('answers', 'score');
                            },
                        ]);
                },
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('subject', function ($subjectQuery) use ($search) {
                        $subjectQuery->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                            $teacherQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();
    }

    /**
     * Get enrollment history for a student.
     *
     * @param  User  $student  The student user
     * @param  int  $academicYearId  Academic year ID
     * @return Collection Collection of enrollments
     */
    public function getEnrollmentHistory(User $student, ?int $academicYearId): Collection
    {
        return Enrollment::where('student_id', $student->id)
            ->when($academicYearId, fn ($q) => $q->forAcademicYear($academicYearId))
            ->with([
                'class.academicYear',
                'class.level',
            ])
            ->orderBy('enrolled_at', 'desc')
            ->get();
    }

    /**
     * Get classmates for a student's enrollment.
     *
     * @param  Enrollment  $enrollment  The student's enrollment
     * @param  User  $student  The student user (to exclude)
     * @return Collection Collection of classmate users
     */
    public function getClassmates(Enrollment $enrollment, User $student): Collection
    {
        return Enrollment::where('class_id', $enrollment->class_id)
            ->where('student_id', '!=', $student->id)
            ->where('status', EnrollmentStatus::Active)
            ->with('student:id,name,email')
            ->limit(50)
            ->get()
            ->pluck('student');
    }

    /**
     * Validate that enrollment belongs to selected academic year.
     *
     * @param  Enrollment  $enrollment  The enrollment to validate
     * @param  int  $selectedYearId  The selected academic year ID
     * @return bool True if valid
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function validateAcademicYearAccess(Enrollment $enrollment, int $selectedYearId): bool
    {
        $enrollment->loadMissing('class');

        if ($enrollment->class->academic_year_id !== $selectedYearId) {
            abort(403, __('messages.enrollment_not_in_selected_year'));
        }

        return true;
    }
}
