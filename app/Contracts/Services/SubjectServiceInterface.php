<?php

namespace App\Contracts\Services;

use App\Models\Subject;

interface SubjectServiceInterface
{
    /**
     * Create a new subject.
     */
    public function createSubject(array $validatedData): Subject;

    /**
     * Update an existing subject.
     */
    public function updateSubject(Subject $subject, array $validatedData): Subject;

    /**
     * Delete a subject.
     */
    public function deleteSubject(Subject $subject): bool;

    /**
     * Check whether a subject has class subject assignments.
     */
    public function hasClassSubjects(Subject $subject): bool;
}
