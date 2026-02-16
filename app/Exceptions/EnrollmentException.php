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
          ? "Class has only {$availableSlots} available slots"
          : 'Class is full';

        return new self($message);
    }

    /**
     * Create exception for invalid student role
     */
    public static function invalidStudentRole(): self
    {
        return new self('User must have student role');
    }

    /**
     * Create exception for duplicate enrollment
     */
    public static function alreadyEnrolled(): self
    {
        return new self('Student already enrolled in a class for this academic year');
    }

    /**
     * Create exception for invalid enrollment status
     */
    public static function invalidStatus(string $currentStatus): self
    {
        return new self("Only withdrawn enrollments can be reactivated (current status: {$currentStatus})");
    }

    /**
     * Create exception for target class being full during transfer
     */
    public static function targetClassFull(): self
    {
        return new self('Target class is full');
    }
}
