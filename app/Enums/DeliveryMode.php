<?php

namespace App\Enums;

/**
 * Defines how a student takes an assessment.
 *
 * Supervised: timed, single-session, security-enforced (exam-like).
 * Homework: deadline-based, multi-session, no security (take-home).
 */
enum DeliveryMode: string
{
    case Supervised = 'supervised';
    case Homework = 'homework';

    /**
     * Get the default delivery mode for a given assessment type.
     */
    public static function defaultForType(AssessmentType|string $type): self
    {
        $assessmentType = $type instanceof AssessmentType
            ? $type
            : AssessmentType::tryFrom($type);

        return match ($assessmentType) {
            AssessmentType::Exam, AssessmentType::Quiz => self::Supervised,
            default => self::Homework,
        };
    }

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
     * Get the human-readable label for this delivery mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::Supervised => __('messages.delivery_mode_supervised'),
            self::Homework => __('messages.delivery_mode_homework'),
        };
    }

    /**
     * Check if this is supervised delivery mode.
     */
    public function isSupervisedMode(): bool
    {
        return $this === self::Supervised;
    }

    /**
     * Check if this is homework delivery mode.
     */
    public function isHomeworkMode(): bool
    {
        return $this === self::Homework;
    }
}
