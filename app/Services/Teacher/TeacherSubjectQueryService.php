<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeacherSubjectQueryService
{
    /**
     * Get all subjects where the teacher is assigned with aggregated data.
     */
    public function getSubjectsForTeacher(
        int $teacherId,
        int $selectedYearId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->with(['subject', 'class'])
            ->get();

        if ($classSubjects->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $assessmentCounts = DB::table('assessments')
            ->whereIn('class_subject_id', $classSubjects->pluck('id'))
            ->select('class_subject_id', DB::raw('COUNT(*) as count'))
            ->groupBy('class_subject_id')
            ->pluck('count', 'class_subject_id');

        $subjectsBySubjectId = $classSubjects->groupBy('subject_id');

        $subjects = $subjectsBySubjectId->map(function ($classSubjects) use ($assessmentCounts) {
            $subject = $classSubjects->first()->subject;
            $subject->classes = $classSubjects->pluck('class')->unique('id');
            $subject->classes_count = $subject->classes->count();

            $subject->assessments_count = $classSubjects->sum(
                fn($cs) => $assessmentCounts->get($cs->id, 0)
            );

            return $subject;
        })->values();

        $subjects = $this->applyFilters($subjects, $filters);

        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = $subjects->count();
        $items = $subjects->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get classes for filter dropdown.
     */
    public function getClassesForFilter(int $teacherId, int $selectedYearId): Collection
    {
        return ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->with('class')
            ->get()
            ->pluck('class')
            ->unique('id')
            ->values();
    }

    /**
     * Authorize that the teacher has access to this subject.
     */
    public function authorizeTeacherSubject(int $teacherId, int $subjectId, int $selectedYearId): void
    {
        $hasAccess = ClassSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->exists();

        if (! $hasAccess) {
            throw new NotFoundHttpException(__('messages.subject_not_found'));
        }
    }

    /**
     * Get subject details with classes info.
     */
    public function getSubjectDetails(Subject $subject, int $teacherId, int $selectedYearId): Subject
    {
        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $subject->id)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->with(['class.academicYear', 'class.level'])
            ->withCount('assessments')
            ->get();

        $subject->classes = $classSubjects->pluck('class')->unique('id');
        $subject->class_subjects = $classSubjects;
        $subject->total_assessments = $classSubjects->sum('assessments_count');

        return $subject;
    }

    /**
     * Get assessments for a subject taught by this teacher.
     */
    public function getAssessmentsForSubject(
        Subject $subject,
        int $teacherId,
        int $selectedYearId,
        array $filters,
        int $perPage
    ): LengthAwarePaginator {
        $classSubjectIds = ClassSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $subject->id)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->pluck('id');

        return Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->with(['classSubject.class'])
            ->when(
                $filters['search'] ?? null,
                fn($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->latest('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Apply search and class filters to the subjects collection.
     */
    protected function applyFilters(Collection $subjects, array $filters): Collection
    {
        if ($search = $filters['search'] ?? null) {
            $search = strtolower($search);
            $subjects = $subjects->filter(function ($subject) use ($search) {
                return str_contains(strtolower($subject->name ?? ''), $search) ||
                    str_contains(strtolower($subject->code ?? ''), $search);
            })->values();
        }

        if ($classId = $filters['class_id'] ?? null) {
            $subjects = $subjects->filter(function ($subject) use ($classId) {
                return $subject->classes->contains('id', $classId);
            })->values();
        }

        return $subjects;
    }
}
