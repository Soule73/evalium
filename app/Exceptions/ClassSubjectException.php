<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Class Subject Exception
 *
 * Thrown when class-subject assignment operations fail
 */
class ClassSubjectException extends Exception
{
    /**
     * Create exception for invalid coefficient
     */
    public static function invalidCoefficient(): self
    {
        return new self(__('messages.class_subject_invalid_coefficient'));
    }

    /**
     * Create exception for level mismatch
     */
    public static function levelMismatch(): self
    {
        return new self(__('messages.class_subject_level_mismatch'));
    }
}
