<?php

namespace Tests\Traits;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
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
        $defaults = [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
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
        return AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
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
        return AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinutes(30),
            'score' => $score,
        ], $attributes));
    }
}
