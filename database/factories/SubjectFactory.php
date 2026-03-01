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
            'Mathematics' => 'MATH',
            'Physics' => 'PHYS',
            'Chemistry' => 'CHEM',
            'Computer Science' => 'INFO',
            'French' => 'FRAN',
            'English' => 'ANGL',
            'History' => 'HIST',
            'Geography' => 'GEOG',
        ];

        $base = $this->faker->randomElement(array_keys($subjects));
        $prefix = $subjects[$base];
        $suffix = $this->faker->unique()->numerify('###');

        return [
            'level_id' => Level::factory(),
            'name' => $base.' '.$suffix,
            'code' => $prefix.'-'.$suffix,
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
