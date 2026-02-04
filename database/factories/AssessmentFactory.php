<?php

namespace Database\Factories;

use App\Models\ClassSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['devoir', 'examen', 'tp', 'controle', 'projet'];

        return [
            'class_subject_id' => ClassSubject::factory(),
            'teacher_id' => User::factory()->teacher(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement($types),
            'coefficient' => $this->faker->randomFloat(1, 1, 3),
            'duration_minutes' => $this->faker->optional()->numberBetween(30, 180),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'settings' => [],
        ];
    }

    public function devoir(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'devoir',
            'coefficient' => 1,
        ]);
    }

    public function examen(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'examen',
            'coefficient' => 2,
            'duration_minutes' => 120,
        ]);
    }

    public function tp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'tp',
            'coefficient' => 1.5,
        ]);
    }
}
