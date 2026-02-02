<?php

namespace App\Services\Admin;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Level;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Class Service - Manage classes (replacing groups)
 *
 * Single Responsibility: Handle class CRUD operations with academic year context
 */
class ClassService
{
    /**
     * Create a new class for an academic year
     */
    public function createClass(array $data): ClassModel
    {
        $this->validateClassData($data);

        return ClassModel::create([
            'academic_year_id' => $data['academic_year_id'],
            'level_id' => $data['level_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_students' => $data['max_students'] ?? 30,
        ]);
    }

    /**
     * Update an existing class
     */
    public function updateClass(ClassModel $class, array $data): ClassModel
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'max_students' => $data['max_students'] ?? null,
        ], fn ($value) => $value !== null);

        $class->update($updateData);

        return $class->fresh();
    }

    /**
     * Delete a class (only if empty)
     */
    public function deleteClass(ClassModel $class): bool
    {
        if ($class->enrollments()->count() > 0) {
            throw new InvalidArgumentException('Cannot delete class with enrolled students');
        }

        if ($class->classSubjects()->count() > 0) {
            throw new InvalidArgumentException('Cannot delete class with subject assignments');
        }

        return $class->delete();
    }

    /**
     * Get all classes for an academic year
     */
    public function getClassesForAcademicYear(AcademicYear $academicYear): Collection
    {
        return ClassModel::where('academic_year_id', $academicYear->id)
            ->with(['level', 'enrollments'])
            ->get();
    }

    /**
     * Get classes for current academic year
     */
    public function getCurrentClasses(): Collection
    {
        return ClassModel::whereHas('academicYear', function ($query) {
            $query->where('is_current', true);
        })
            ->with(['level', 'academicYear', 'enrollments'])
            ->get();
    }

    /**
     * Get students in a class
     */
    public function getStudentsInClass(ClassModel $class): Collection
    {
        return $class->students;
    }

    /**
     * Duplicate classes from one academic year to another
     */
    public function duplicateClassesToNewYear(
        AcademicYear $sourceYear,
        AcademicYear $targetYear,
        array $classIds = []
    ): Collection {
        $query = ClassModel::where('academic_year_id', $sourceYear->id);

        if (! empty($classIds)) {
            $query->whereIn('id', $classIds);
        }

        $sourceClasses = $query->get();
        $newClasses = collect();

        DB::transaction(function () use ($sourceClasses, $targetYear, &$newClasses) {
            foreach ($sourceClasses as $sourceClass) {
                $newClass = ClassModel::create([
                    'academic_year_id' => $targetYear->id,
                    'level_id' => $sourceClass->level_id,
                    'name' => $sourceClass->name,
                    'description' => $sourceClass->description,
                    'max_students' => $sourceClass->max_students,
                ]);

                $newClasses->push($newClass);
            }
        });

        return $newClasses;
    }

    /**
     * Get class statistics
     */
    public function getClassStatistics(ClassModel $class): array
    {
        return [
            'total_students' => $class->enrollments()->count(),
            'active_students' => $class->enrollments()->where('status', 'active')->count(),
            'max_students' => $class->max_students,
            'available_slots' => $class->max_students - $class->enrollments()->count(),
            'subjects_count' => $class->classSubjects()->active()->count(),
            'assessments_count' => $class->classSubjects()
                ->active()
                ->withCount('assessments')
                ->get()
                ->sum('assessments_count'),
        ];
    }

    /**
     * Get classes by level
     */
    public function getClassesByLevel(Level $level, ?int $academicYearId = null): Collection
    {
        $query = ClassModel::where('level_id', $level->id);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        } else {
            $query->whereHas('academicYear', function ($q) {
                $q->where('is_current', true);
            });
        }

        return $query->with(['academicYear', 'enrollments'])->get();
    }

    /**
     * Validate class data
     */
    private function validateClassData(array $data): void
    {
        $required = ['academic_year_id', 'level_id', 'name'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $existingClass = ClassModel::where('academic_year_id', $data['academic_year_id'])
            ->where('level_id', $data['level_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($existingClass) {
            throw new InvalidArgumentException('A class with this name already exists for this level and academic year');
        }
    }
}
