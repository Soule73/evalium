<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAssignment>
 */
class AssessmentAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => User::factory()->student(),
            'submitted_at' => null,
            'graded_at' => null,
            'score' => null,
        ];
    }

    public function submitted(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-5 days', 'now');

        return $this->state(fn (array $attributes) => [
            'submitted_at' => $submittedAt,
            'graded_at' => null,
            'score' => null,
        ]);
    }

    public function graded(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-10 days', '-2 days');
        $gradedAt = $this->faker->dateTimeBetween($submittedAt, 'now');

        return $this->state(fn (array $attributes) => [
            'submitted_at' => $submittedAt,
            'graded_at' => $gradedAt,
            'score' => $this->faker->randomFloat(2, 0, 20),
        ]);
    }
}
