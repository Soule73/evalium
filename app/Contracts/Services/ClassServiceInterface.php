<?php

namespace App\Contracts\Services;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use Illuminate\Support\Collection;

interface ClassServiceInterface
{
    /**
     * Create a new class.
     */
    public function createClass(array $data): ClassModel;

    /**
     * Update an existing class.
     */
    public function updateClass(ClassModel $class, array $data): ClassModel;

    /**
     * Delete a class.
     */
    public function deleteClass(ClassModel $class): bool;

    /**
     * Duplicate classes from a source year to a target year.
     */
    public function duplicateClassesToNewYear(AcademicYear $sourceYear, AcademicYear $targetYear, array $classIds = []): Collection;
}
