<?php

namespace App\Services\Core;

use App\Contracts\Repositories\ClassSubjectRepositoryInterface;
use App\Contracts\Services\ClassSubjectServiceInterface;
use App\Exceptions\ClassSubjectException;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ClassSubject Service - THE CENTRAL SERVICE
 *
 * Single Responsibility: Manage teacher-subject-class assignments with historization
 */
class ClassSubjectService implements ClassSubjectServiceInterface
{
    public function __construct(
        private readonly ClassSubjectRepositoryInterface $classSubjectRepository
    ) {}

    /**
     * Get form data for creation of a class-subject assignment.
     */
    public function getFormDataForCreate(?int $selectedYearId): array
    {
        return [
            'classes' => ClassModel::query()
                ->when($selectedYearId, fn ($q) => $q->forAcademicYear($selectedYearId))
                ->with('level:id,name,description')
                ->orderBy('name')
                ->get(['id', 'name', 'level_id', 'academic_year_id']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name', 'code', 'level_id']),
            'teachers' => User::role('teacher')->orderBy('name')->get(['id', 'name', 'email']),
            'semesters' => Semester::query()
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->orderBy('order_number')
                ->get(['id', 'name', 'order_number', 'academic_year_id']),
        ];
    }

    /**
     * Assign a teacher to teach a subject in a class
     */
    public function assignTeacherToClassSubject(array $data): ClassSubject
    {
        $this->validateAssignment($data);

        return DB::transaction(function () use ($data) {
            return ClassSubject::create([
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'semester_id' => $data['semester_id'] ?? null,
                'coefficient' => $data['coefficient'],
                'valid_from' => $data['valid_from'] ?? now()->toDateString(),
                'valid_to' => null,
            ]);
        });
    }

    /**
     * Replace a teacher for a class-subject (historization)
     */
    public function replaceTeacher(
        ClassSubject $classSubject,
        int $newTeacherId,
        ?Carbon $effectiveDate = null
    ): ClassSubject {
        if ($classSubject->valid_to !== null) {
            throw ClassSubjectException::alreadyTerminated();
        }

        $effectiveDate = $effectiveDate ?? now();

        return DB::transaction(function () use ($classSubject, $newTeacherId, $effectiveDate) {
            $classSubject->update(['valid_to' => $effectiveDate->copy()->subDay()]);

            return ClassSubject::create([
                'class_id' => $classSubject->class_id,
                'subject_id' => $classSubject->subject_id,
                'teacher_id' => $newTeacherId,
                'semester_id' => $classSubject->semester_id,
                'coefficient' => $classSubject->coefficient,
                'valid_from' => $effectiveDate,
                'valid_to' => null,
            ]);
        });
    }

    /**
     * Get teaching history for a class-subject combination
     */
    public function getTeachingHistory(int $classId, int $subjectId): Collection
    {
        return $this->classSubjectRepository->getHistory($classId, $subjectId);
    }

    /**
     * Update coefficient for a class-subject
     */
    public function updateCoefficient(ClassSubject $classSubject, float $coefficient): ClassSubject
    {
        if ($coefficient <= 0) {
            throw ClassSubjectException::invalidCoefficient();
        }

        $classSubject->update(['coefficient' => $coefficient]);

        return $classSubject->fresh();
    }

    /**
     * Terminate a class-subject assignment
     */
    public function terminateAssignment(ClassSubject $classSubject, ?Carbon $endDate = null): ClassSubject
    {
        $endDate = $endDate ?? now();

        $classSubject->update(['valid_to' => $endDate]);

        return $classSubject->fresh();
    }

    /**
     * Validate assignment data
     */
    private function validateAssignment(array $data): void
    {
        if (ClassSubject::where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->whereNull('valid_to')
            ->exists()) {
            throw ClassSubjectException::alreadyActive();
        }

        $class = ClassModel::find($data['class_id']);
        $subject = Subject::find($data['subject_id']);

        if ($class && $subject && $class->level_id !== $subject->level_id) {
            throw ClassSubjectException::levelMismatch();
        }
    }

    /**
     * Delete a class-subject assignment, throwing if assessments exist.
     */
    public function deleteClassSubject(ClassSubject $classSubject): void
    {
        if ($classSubject->assessments()->exists()) {
            throw ClassSubjectException::hasAssessments();
        }

        $classSubject->delete();
    }
}
