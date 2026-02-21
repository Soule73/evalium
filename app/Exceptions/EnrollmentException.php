<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Enrollment Exception
 *
 * Thrown when enrollment operations fail due to business rule violations
 */
class EnrollmentException extends Exception
{
    /**
     * Create exception for when class is full
     */
    public static function classFull(?int $availableSlots = null): self
    {
        $message = $availableSlots !== null
            ? __('messages.enrollment_class_full_slots', ['slots' => $availableSlots])
            : __('messages.enrollment_class_full');

        return new self($message);
    }

    /**
     * Create exception for invalid student role
     */
    public static function invalidStudentRole(): self
    {
        return new self(__('messages.enrollment_invalid_student_role'));
    }

    /**
     * Create exception for duplicate enrollment
     */
    public static function alreadyEnrolled(): self
    {
        return new self(__('messages.student_already_enrolled'));
    }

    /**
     * Create exception for invalid enrollment status
     */
    public static function invalidStatus(string $currentStatus): self
    {
        return new self(__('messages.enrollment_invalid_status', ['status' => $currentStatus]));
    }

    /**
     * Create exception for target class being full during transfer
     */
    public static function targetClassFull(): self
    {
        return new self(__('messages.enrollment_target_class_full'));
    }
}
