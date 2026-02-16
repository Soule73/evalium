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
        return new self('Coefficient must be greater than 0');
    }

    /**
     * Create exception for level mismatch
     */
    public static function levelMismatch(): self
    {
        return new self('Subject level must match class level');
    }
}
