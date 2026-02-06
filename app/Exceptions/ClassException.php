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
     * Create exception for deletion with enrolled students
     */
    public static function hasEnrolledStudents(): self
    {
        return new self('Cannot delete class with enrolled students');
    }

    /**
     * Create exception for deletion with subject assignments
     */
    public static function hasSubjectAssignments(): self
    {
        return new self('Cannot delete class with subject assignments');
    }

    /**
     * Create exception for duplicate class name
     */
    public static function duplicateName(): self
    {
        return new self('A class with this name already exists for this level and academic year');
    }
}
