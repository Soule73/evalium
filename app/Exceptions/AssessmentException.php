<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Assessment Exception
 *
 * Thrown when assessment operations fail due to business rule violations
 */
class AssessmentException extends Exception
{
    /**
     * Create exception for invalid coefficient
     */
    public static function invalidCoefficient(): self
    {
        return new self('Coefficient must be greater than 0');
    }

    /**
     * Create exception for invalid duration
     */
    public static function invalidDuration(): self
    {
        return new self('Duration must be greater than 0');
    }

    /**
     * Create exception for invalid assessment type
     */
    public static function invalidType(string $type): self
    {
        return new self("Invalid assessment type: {$type}");
    }

    /**
     * Create exception for deletion with existing assignments
     */
    public static function hasExistingAssignments(): self
    {
        return new self('Cannot delete assessment with existing student assignments');
    }
}
