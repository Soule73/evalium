<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Class Exception
 *
 * Thrown when class operations fail due to business rule violations
 */
class ClassException extends Exception
{
    /**
     * Create exception for deletion with enrolled students.
     */
    public static function hasEnrolledStudents(): self
    {
        return new self(__('messages.class_has_enrolled_students'));
    }

    /**
     * Create exception for deletion with subject assignments.
     */
    public static function hasSubjectAssignments(): self
    {
        return new self(__('messages.class_has_subject_assignments'));
    }
}
