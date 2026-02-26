<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\AdminAssessmentRepositoryInterface;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Admin Assessment Query Service
 *
 * Handles read-only queries for assessments from the admin perspective.
 * Provides paginated assessment listings for classes, teachers, and students.
 */
class AdminAssessmentRepository implements AdminAssessmentRepositoryInterface
{
    use Paginatable;

    /**
     * Get paginated assessments for a specific class.
     *
     * @param  ClassModel  $class  The class to query assessments for
     * @param  array  $filters  Search/filter criteria (search, subject_id, teacher_id, type, delivery_mode, status)
     * @param  int  $perPage  Items per page
     */
    public function getAssessmentsForClass(ClassModel $class, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Assessment::query()
            ->join('class_subjects', 'class_subjects.id', '=', 'assessments.class_subject_id')
            ->where('class_subjects.class_id', $class->id)
            ->select('assessments.*')
            ->with(['classSubject.subject:id,name,code', 'teacher:id,name,email'])
            ->withCount(['questions', 'assignments'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['subject_id'] ?? null, fn ($query, $subjectId) => $query->where('class_subjects.subject_id', $subjectId))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->where('teacher_id', $teacherId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['delivery_mode'] ?? null, fn ($query, $mode) => $query->where('delivery_mode', $mode))
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($query) use ($filters) {
                $isPublished = $filters['status'] === 'published';
                $query->where('is_published', $isPublished);
            })
            ->orderBy('created_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $perPage, 'page' => $filters['page'] ?? 1, 'page_name' => 'assessments_page'],
            array_filter([
                'search' => $filters['search'] ?? null,
                'subject_id' => $filters['subject_id'] ?? null,
                'teacher_id' => $filters['teacher_id'] ?? null,
                'type' => $filters['type'] ?? null,
                'delivery_mode' => $filters['delivery_mode'] ?? null,
                'status' => $filters['status'] ?? null,
            ])
        );
    }

    /**
     * Get paginated assessments created by a specific teacher.
     *
     * @param  User  $teacher  The teacher whose assessments to retrieve
     * @param  array  $filters  Search/filter criteria (search, type, delivery_mode, status)
     * @param  int  $perPage  Items per page
     */
    public function getAssessmentsForTeacher(User $teacher, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Assessment::query()
            ->where('teacher_id', $teacher->id)
            ->with(['classSubject.subject', 'classSubject.class.level'])
            ->withCount(['questions', 'assignments'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['delivery_mode'] ?? null, fn ($query, $mode) => $query->where('delivery_mode', $mode))
            ->orderBy('created_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $perPage, 'page' => $filters['page'] ?? 1],
            array_filter([
                'search' => $filters['search'] ?? null,
                'type' => $filters['type'] ?? null,
                'delivery_mode' => $filters['delivery_mode'] ?? null,
            ])
        );
    }

    /**
     * Get paginated assessment assignments for a specific student.
     *
     * @param  User  $student  The student whose assignments to retrieve
     * @param  array  $filters  Search/filter criteria (search, subject_id, status)
     * @param  int  $perPage  Items per page
     */
    public function getAssignmentsForStudent(User $student, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = AssessmentAssignment::query()
            ->forStudent($student)
            ->with([
                'assessment.classSubject.subject',
                'assessment.classSubject.class.level',
                'assessment.classSubject.teacher',
                'assessment.teacher',
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('assessment', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['subject_id'] ?? null, function ($query, $subjectId) {
                $query->whereHas('assessment.classSubject', fn ($q) => $q->where('subject_id', $subjectId));
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                match ($status) {
                    'graded' => $query->whereNotNull('graded_at'),
                    'submitted' => $query->whereNotNull('submitted_at')->whereNull('graded_at'),
                    'in_progress' => $query->whereNotNull('started_at')->whereNull('submitted_at'),
                    'not_submitted' => $query->whereNull('started_at'),
                    default => null,
                };
            })
            ->orderBy('created_at', 'desc');

        return $this->paginateWithFilters(
            $query,
            ['per_page' => $perPage, 'page' => $filters['page'] ?? 1],
            array_filter([
                'search' => $filters['search'] ?? null,
                'subject_id' => $filters['subject_id'] ?? null,
                'status' => $filters['status'] ?? null,
            ])
        );
    }

    /**
     * Get teacher assessment statistics.
     *
     * Computes total and published counts in a single query.
     * Uses `= true` (not `= 1`) because MySQL's JSON_EXTRACT returns a JSON boolean
     * which is not equal to the integer 1 in a JSON comparison context.
     *
     * @param  User  $teacher  The teacher to compute stats for
     * @return array{total: int, published: int, unpublished: int}
     */
    public function getTeacherAssessmentStats(User $teacher): array
    {
        $row = Assessment::query()
            ->where('teacher_id', $teacher->id)
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published'
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $published = (int) ($row->published ?? 0);

        return [
            'total' => $total,
            'published' => $published,
            'unpublished' => $total - $published,
        ];
    }

    /**
     * Get all assessments with full filtering (for admin global index).
     *
     * @param  int|null  $academicYearId  Academic year scope
     * @param  array  $filters  Search/filter criteria
     * @param  int  $perPage  Items per page
     */
    public function getAllAssessments(?int $academicYearId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Assessment::query()
            ->with([
                'classSubject.subject',
                'classSubject.class.level',
                'classSubject.teacher',
                'teacher',
            ])
            ->when($academicYearId, function ($query, $yearId) {
                $query->whereHas('classSubject.class', fn ($q) => $q->where('academic_year_id', $yearId));
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['class_id'] ?? null, function ($query, $classId) {
                $query->whereHas('classSubject', fn ($q) => $q->where('class_id', $classId));
            })
            ->when($filters['subject_id'] ?? null, function ($query, $subjectId) {
                $query->whereHas('classSubject', fn ($q) => $q->where('subject_id', $subjectId));
            })
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->where('teacher_id', $teacherId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['delivery_mode'] ?? null, fn ($query, $mode) => $query->where('delivery_mode', $mode))
            ->orderBy('created_at', 'desc');

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Get filter data (classes, subjects, teachers) for the admin assessment index.
     *
     * @return array{classes: \Illuminate\Support\Collection, subjects: \Illuminate\Support\Collection, teachers: \Illuminate\Support\Collection}
     */
    public function getFilterData(?int $academicYearId): array
    {
        return [
            'classes' => ClassModel::query()
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'teachers' => User::role('teacher')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
