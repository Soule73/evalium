<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassModel>
 */
class ClassModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $classNames = ['A', 'B', 'C', 'D'];

        return [
            'academic_year_id' => AcademicYear::factory(),
            'level_id' => Level::factory(),
            'name' => $this->faker->randomElement($classNames),
            'description' => $this->faker->optional()->sentence(),
            'max_students' => $this->faker->optional()->numberBetween(20, 40),
        ];
    }
}
