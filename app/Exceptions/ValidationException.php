<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Validation Exception
 *
 * Thrown when data validation fails in service layer
 */
class ValidationException extends Exception
{
    /**
     * Create exception for missing required field
     */
    public static function missingRequiredField(string $fieldName): self
    {
        return new self("Missing required field: {$fieldName}");
    }

    /**
     * Create exception for invalid field value
     */
    public static function invalidFieldValue(string $fieldName, string $reason): self
    {
        return new self("Invalid value for field '{$fieldName}': {$reason}");
    }

    /**
     * Create exception for multiple validation errors
     */
    public static function multipleErrors(array $errors): self
    {
        $message = 'Validation failed: '.implode(', ', $errors);

        return new self($message);
    }
}
