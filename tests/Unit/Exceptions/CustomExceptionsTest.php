<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\AssessmentException;
use App\Exceptions\ClassException;
use App\Exceptions\ClassSubjectException;
use App\Exceptions\EnrollmentException;
use App\Exceptions\ValidationException;
use Tests\TestCase;

class CustomExceptionsTest extends TestCase
{
    public function test_enrollment_exception_class_full(): void
    {
        $exception = EnrollmentException::classFull();

        $this->assertInstanceOf(EnrollmentException::class, $exception);
        $this->assertEquals(__('messages.enrollment_class_full'), $exception->getMessage());
    }

    public function test_enrollment_exception_class_full_with_slots(): void
    {
        $exception = EnrollmentException::classFull(5);

        $this->assertEquals(__('messages.enrollment_class_full_slots', ['slots' => 5]), $exception->getMessage());
    }

    public function test_enrollment_exception_invalid_student_role(): void
    {
        $exception = EnrollmentException::invalidStudentRole();

        $this->assertEquals(__('messages.enrollment_invalid_student_role'), $exception->getMessage());
    }

    public function test_enrollment_exception_already_enrolled(): void
    {
        $exception = EnrollmentException::alreadyEnrolled();

        $this->assertEquals(__('messages.student_already_enrolled'), $exception->getMessage());
    }

    public function test_enrollment_exception_invalid_status(): void
    {
        $exception = EnrollmentException::invalidStatus('active');

        $this->assertStringContainsString('withdrawn', $exception->getMessage());
        $this->assertStringContainsString('active', $exception->getMessage());
    }

    public function test_enrollment_exception_target_class_full(): void
    {
        $exception = EnrollmentException::targetClassFull();

        $this->assertEquals(__('messages.enrollment_target_class_full'), $exception->getMessage());
    }

    public function test_class_exception_has_enrolled_students(): void
    {
        $exception = ClassException::hasEnrolledStudents();

        $this->assertInstanceOf(ClassException::class, $exception);
        $this->assertStringContainsString('enrolled students', $exception->getMessage());
    }

    public function test_class_exception_has_subject_assignments(): void
    {
        $exception = ClassException::hasSubjectAssignments();

        $this->assertStringContainsString('subject assignments', $exception->getMessage());
    }

    public function test_assessment_exception_invalid_coefficient(): void
    {
        $exception = AssessmentException::invalidCoefficient();

        $this->assertInstanceOf(AssessmentException::class, $exception);
        $this->assertStringContainsString('coefficient', strtolower($exception->getMessage()));
        $this->assertStringContainsString('greater than 0', $exception->getMessage());
    }

    public function test_assessment_exception_invalid_duration(): void
    {
        $exception = AssessmentException::invalidDuration();

        $this->assertStringContainsString('duration', strtolower($exception->getMessage()));
    }

    public function test_assessment_exception_invalid_type(): void
    {
        $exception = AssessmentException::invalidType('unknown');

        $this->assertStringContainsString('Invalid', $exception->getMessage());
        $this->assertStringContainsString('unknown', $exception->getMessage());
    }

    public function test_assessment_exception_has_existing_assignments(): void
    {
        $exception = AssessmentException::hasExistingAssignments();

        $this->assertStringContainsString('Cannot delete', $exception->getMessage());
        $this->assertStringContainsString('assignments', $exception->getMessage());
    }

    public function test_class_subject_exception_invalid_coefficient(): void
    {
        $exception = ClassSubjectException::invalidCoefficient();

        $this->assertInstanceOf(ClassSubjectException::class, $exception);
        $this->assertStringContainsString('coefficient', strtolower($exception->getMessage()));
    }

    public function test_class_subject_exception_level_mismatch(): void
    {
        $exception = ClassSubjectException::levelMismatch();

        $this->assertStringContainsString('level', $exception->getMessage());
        $this->assertStringContainsString('match', $exception->getMessage());
    }

    public function test_validation_exception_missing_required_field(): void
    {
        $exception = ValidationException::missingRequiredField('email');

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertStringContainsString('Missing required field', $exception->getMessage());
        $this->assertStringContainsString('email', $exception->getMessage());
    }

    public function test_validation_exception_invalid_field_value(): void
    {
        $exception = ValidationException::invalidFieldValue('age', 'must be positive');

        $this->assertStringContainsString('Invalid value', $exception->getMessage());
        $this->assertStringContainsString('age', $exception->getMessage());
        $this->assertStringContainsString('must be positive', $exception->getMessage());
    }

    public function test_validation_exception_multiple_errors(): void
    {
        $errors = ['Name is required', 'Email is invalid'];
        $exception = ValidationException::multipleErrors($errors);

        $this->assertStringContainsString('Validation failed', $exception->getMessage());
        $this->assertStringContainsString('Name is required', $exception->getMessage());
        $this->assertStringContainsString('Email is invalid', $exception->getMessage());
    }
}
