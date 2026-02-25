<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Contracts\Services\ClassServiceInterface;
use App\Exceptions\ClassException;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class Service - Manage class CRUD operations
 *
 * Handles create, update, delete, and duplicate operations for classes.
 * Query operations are delegated to ClassQueryService following SRP.
 */
class ClassService implements ClassServiceInterface
{
    public function __construct(
        private readonly ClassRepositoryInterface $classQueryService
    ) {}

    /**
     * Get form data for the create page.
     */
    public function getCreateFormData(int $selectedYearId): array
    {
        return [
            'levels' => $this->classQueryService->getAllLevels(),
            'selectedAcademicYear' => AcademicYear::find($selectedYearId),
        ];
    }

    /**
     * Get form data for the edit page.
     */
    public function getEditFormData(ClassModel $class): array
    {
        return [
            'class' => $class->load(['academicYear', 'level']),
            'levels' => $this->classQueryService->getAllLevels(),
        ];
    }

    /**
     * Create a new class for an academic year
     */
    public function createClass(array $data): ClassModel
    {
        $class = ClassModel::create([
            'academic_year_id' => $data['academic_year_id'],
            'level_id' => $data['level_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_students' => $data['max_students'] ?? 30,
        ]);

        $this->classQueryService->invalidateLevelsCache();

        return $class;
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

        $this->classQueryService->invalidateLevelsCache();

        return $class->fresh();
    }

    /**
     * Delete a class (only if empty)
     */
    public function deleteClass(ClassModel $class): bool
    {
        if ($class->enrollments()->count() > 0) {
            throw ClassException::hasEnrolledStudents();
        }

        if ($class->classSubjects()->count() > 0) {
            throw ClassException::hasSubjectAssignments();
        }

        return $class->delete();
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
}
