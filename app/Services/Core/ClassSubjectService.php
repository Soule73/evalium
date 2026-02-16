<?php

namespace App\Services\Core;

use App\Exceptions\ClassSubjectException;
use App\Exceptions\ValidationException;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ClassSubject Service - THE CENTRAL SERVICE
 *
 * Single Responsibility: Manage teacher-subject-class assignments with historization
 */
class ClassSubjectService
{
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
                'teacher_id' => $data['teacher_id'],
                'semester_id' => $data['semester_id'],
                'coefficient' => $data['coefficient'],
                'valid_from' => $data['valid_from'] ?? now(),
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
        return ClassSubject::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->with(['teacher', 'semester'])
            ->orderBy('valid_from')
            ->get();
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
     * Get all class-subjects for an academic year
     */
    public function getClassSubjectsForAcademicYear(int $academicYearId, bool $activeOnly = true): Collection
    {
        $query = ClassSubject::whereHas('class', function ($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId);
        })->with(['class', 'subject', 'teacher', 'semester']);

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Validate assignment data
     */
    private function validateAssignment(array $data): void
    {
        $required = ['class_id', 'subject_id', 'teacher_id', 'semester_id', 'coefficient'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw ValidationException::missingRequiredField($field);
            }
        }

        if ($data['coefficient'] <= 0) {
            throw ClassSubjectException::invalidCoefficient();
        }

        $class = ClassModel::find($data['class_id']);
        $subject = Subject::find($data['subject_id']);

        if ($class && $subject && $class->level_id !== $subject->level_id) {
            throw ClassSubjectException::levelMismatch();
        }
    }
}
