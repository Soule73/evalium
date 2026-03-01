<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Subject Exception
 *
 * Thrown when subject operations fail due to business rule violations
 */
class SubjectException extends Exception
{
    /**
     * Create exception for when subject has class subject assignments
     */
    public static function hasClassSubjects(): self
    {
        return new self(__('messages.subject_has_class_subjects'));
    }
}
