<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => ClassModel::factory(),
            'student_id' => User::factory()->student(),
            'enrolled_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'withdrawn_at' => null,
            'status' => 'active',
        ];
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'withdrawn',
            'withdrawn_at' => now()->subDays($this->faker->numberBetween(1, 10)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
