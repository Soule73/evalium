<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSubject>
 */
class ClassSubjectFactory extends Factory
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
            'subject_id' => Subject::factory(),
            'teacher_id' => User::factory()->teacher(),
            'semester_id' => null,
            'coefficient' => $this->faker->randomFloat(1, 1, 5),
            'valid_from' => now()->subDays($this->faker->numberBetween(1, 30)),
            'valid_to' => null,
        ];
    }

    public function historical(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_to' => now()->subDays($this->faker->numberBetween(1, 10)),
        ]);
    }
}
