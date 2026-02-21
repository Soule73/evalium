<?php

namespace App\Services\Admin;

use App\Contracts\Services\SubjectServiceInterface;
use App\Models\Subject;

/**
 * Subject Service - Manage subjects
 *
 * Performance: Uses cache for frequently accessed data
 */
class SubjectService implements SubjectServiceInterface
{
    /**
     * Create a new subject
     */
    public function createSubject(array $validatedData): Subject
    {
        return Subject::create($validatedData);
    }

    /**
     * Update a subject
     */
    public function updateSubject(Subject $subject, array $validatedData): Subject
    {
        $subject->update($validatedData);

        return $subject;
    }

    /**
     * Delete a subject
     */
    public function deleteSubject(Subject $subject): bool
    {
        return $subject->delete();
    }

    /**
     * Check if subject has class subjects
     */
    public function hasClassSubjects(Subject $subject): bool
    {
        return $subject->classSubjects()->exists();
    }
}
