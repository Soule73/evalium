<?php

namespace Tests\Traits;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use App\Models\User;

trait CreatesTestAssignments
{
    /**
     * Create an assignment for a student with custom attributes.
     */
    protected function createAssignmentForStudent(
        Assessment $assessment,
        User $student,
        array $attributes = []
    ): AssessmentAssignment {
        $enrollment = $this->resolveTestEnrollment($student, $assessment);

        $defaults = [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ];

        return AssessmentAssignment::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * Create a submitted assignment.
     */
    protected function createSubmittedAssignment(
        Assessment $assessment,
        User $student,
        array $attributes = []
    ): AssessmentAssignment {
        $enrollment = $this->resolveTestEnrollment($student, $assessment);

        return AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subMinutes(30),
        ], $attributes));
    }

    /**
     * Create a graded assignment.
     */
    protected function createGradedAssignment(
        Assessment $assessment,
        User $student,
        float $score = 75.0,
        array $attributes = []
    ): AssessmentAssignment {
        $enrollment = $this->resolveTestEnrollment($student, $assessment);

        return AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinutes(30),
            'score' => $score,
        ], $attributes));
    }

    /**
     * Resolve or create an enrollment for a student in the assessment's class.
     */
    private function resolveTestEnrollment(User $student, Assessment $assessment): Enrollment
    {
        $assessment->loadMissing('classSubject');

        return Enrollment::firstOrCreate(
            [
                'student_id' => $student->id,
                'class_id' => $assessment->classSubject->class_id,
            ],
            [
                'enrolled_at' => now(),
                'status' => 'active',
            ]
        );
    }
}
