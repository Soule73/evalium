<?php

namespace App\Enums;

/**
 * Defines the type of assessment.
 */
enum AssessmentType: string
{
    case Homework = 'homework';
    case Exam = 'exam';
    case Practical = 'practical';
    case Quiz = 'quiz';
    case Project = 'project';

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
            self::Homework => __('messages.assessment_type_homework'),
            self::Exam => __('messages.assessment_type_exam'),
            self::Practical => __('messages.assessment_type_practical'),
            self::Quiz => __('messages.assessment_type_quiz'),
            self::Project => __('messages.assessment_type_project'),
        };
    }
}
