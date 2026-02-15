<?php

namespace App\Enums;

/**
 * Defines the type of question in an assessment.
 */
enum QuestionType: string
{
    case Text = 'text';
    case Multiple = 'multiple';
    case OneChoice = 'one_choice';
    case Boolean = 'boolean';

    /**
     * Get all valid values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if this type requires manual grading.
     */
    public function requiresManualGrading(): bool
    {
        return $this === self::Text;
    }

    /**
     * Check if this type is auto-correctable.
     */
    public function isAutoCorrecteable(): bool
    {
        return in_array($this, [self::Multiple, self::OneChoice, self::Boolean]);
    }
}
