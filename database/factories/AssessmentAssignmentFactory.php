<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAssignment>
 */
class AssessmentAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'enrollment_id' => Enrollment::factory(),
            'started_at' => null,
            'submitted_at' => null,
            'graded_at' => null,
            'score' => null,
        ];
    }

    /**
     * Link assignment to a student enrolled in the assessment's class.
     *
     * Resolves or creates the enrollment automatically.
     */
    public function forStudentInAssessment(User $student, Assessment $assessment): static
    {
        $assessment->loadMissing('classSubject');

        $enrollment = Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'class_id' => $assessment->classSubject->class_id],
            ['enrolled_at' => now(), 'status' => 'active']
        );

        return $this->state(fn() => [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function started(): static
    {
        return $this->state(fn(array $attributes) => [
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function submitted(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-5 days', 'now');

        return $this->state(fn(array $attributes) => [
            'submitted_at' => $submittedAt,
            'graded_at' => null,
            'score' => null,
        ]);
    }

    public function graded(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-10 days', '-2 days');
        $gradedAt = $this->faker->dateTimeBetween($submittedAt, 'now');

        return $this->state(fn(array $attributes) => [
            'submitted_at' => $submittedAt,
            'graded_at' => $gradedAt,
            'score' => $this->faker->randomFloat(2, 0, 20),
        ]);
    }
}
