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
    private static int $nameSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = range('A', 'Z');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'level_id' => Level::factory(),
            'name' => $names[self::$nameSequence++ % 26],
            'description' => $this->faker->optional()->sentence(),
            'max_students' => $this->faker->numberBetween(20, 40),
        ];
    }
}
