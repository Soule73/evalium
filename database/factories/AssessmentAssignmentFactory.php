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
            'assigned_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'feedback' => null,
        ];
    }

    public function started(): static
    {
        $assignedAt = $this->faker->dateTimeBetween('-30 days', '-1 day');

        return $this->state(fn (array $attributes) => [
            'assigned_at' => $assignedAt,
            'started_at' => $this->faker->dateTimeBetween($assignedAt, 'now'),
        ]);
    }

    public function submitted(): static
    {
        $assignedAt = $this->faker->dateTimeBetween('-10 days', '-5 days');
        $startedAt = $this->faker->dateTimeBetween($assignedAt, '-2 days');

        return $this->state(fn (array $attributes) => [
            'assigned_at' => $assignedAt,
            'started_at' => $startedAt,
            'submitted_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'score' => null,
        ]);
    }

    public function graded(): static
    {
        $assignedAt = $this->faker->dateTimeBetween('-15 days', '-10 days');
        $startedAt = $this->faker->dateTimeBetween($assignedAt, '-8 days');
        $submittedAt = $this->faker->dateTimeBetween($startedAt, '-5 days');

        return $this->state(fn (array $attributes) => [
            'assigned_at' => $assignedAt,
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'score' => $this->faker->randomFloat(2, 0, 20),
            'feedback' => $this->faker->optional()->sentence(),
        ]);
    }
}
