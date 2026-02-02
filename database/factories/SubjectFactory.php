<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Mathématiques' => 'MATH',
            'Physique' => 'PHYS',
            'Chimie' => 'CHEM',
            'Informatique' => 'INFO',
            'Français' => 'FRAN',
            'Anglais' => 'ANGL',
            'Histoire' => 'HIST',
            'Géographie' => 'GEOG',
        ];

        $subject = $this->faker->randomElement(array_keys($subjects));

        return [
            'level_id' => Level::factory(),
            'name' => $subject,
            'code' => $subjects[$subject],
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
