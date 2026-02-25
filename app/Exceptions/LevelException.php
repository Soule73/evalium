<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Level Exception
 *
 * Thrown when level operations fail due to business rule violations
 */
class LevelException extends Exception
{
    /**
     * Create exception for when level has associated classes
     */
    public static function hasClasses(): self
    {
        return new self(__('messages.level_cannot_delete_with_classes'));
    }
}
