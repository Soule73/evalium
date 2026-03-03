<?php

namespace App\Enums;

/**
 * Defines the lifecycle status of a grade report (bulletin).
 */
enum GradeReportStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Published = 'published';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
