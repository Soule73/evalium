<?php

namespace App\Enums;

/**
 * Defines the status of a student's enrollment in a class.
 */
enum EnrollmentStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';

    /**
     * Get all valid values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
