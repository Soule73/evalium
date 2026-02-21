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
        return new self(__('messages.assessment_invalid_coefficient'));
    }

    /**
     * Create exception for invalid duration
     */
    public static function invalidDuration(): self
    {
        return new self(__('messages.assessment_invalid_duration'));
    }

    /**
     * Create exception for invalid assessment type
     */
    public static function invalidType(string $type): self
    {
        return new self(__('messages.assessment_invalid_type', ['type' => $type]));
    }

    /**
     * Create exception for deletion with existing assignments
     */
    public static function hasExistingAssignments(): self
    {
        return new self(__('messages.assessment_has_assignments'));
    }
}
