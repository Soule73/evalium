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
     * Delete a subject, throwing if class subject assignments exist.
     */
    public function deleteSubject(Subject $subject): bool;
}
