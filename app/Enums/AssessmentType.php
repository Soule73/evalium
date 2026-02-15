<?php

namespace App\Enums;

/**
 * Defines the type of assessment.
 */
enum AssessmentType: string
{
    case Devoir = 'devoir';
    case Examen = 'examen';
    case Tp = 'tp';
    case Controle = 'controle';
    case Projet = 'projet';

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
     * Get the human-readable label for this assessment type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Devoir => __('messages.assessment_type_devoir'),
            self::Examen => __('messages.assessment_type_examen'),
            self::Tp => __('messages.assessment_type_tp'),
            self::Controle => __('messages.assessment_type_controle'),
            self::Projet => __('messages.assessment_type_projet'),
        };
    }
}
