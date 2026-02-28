<?php

namespace App\Services\Admin;

use App\Contracts\Services\SubjectServiceInterface;
use App\Exceptions\SubjectException;
use App\Models\Subject;

/**
 * Subject Service - Manage subjects
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

        return $subject->refresh();
    }

    /**
     * Delete a subject, throwing if class subject assignments exist.
     */
    public function deleteSubject(Subject $subject): bool
    {
        if (! $subject->canBeDeleted()) {
            throw SubjectException::hasClassSubjects();
        }

        return $subject->delete();
    }
}
