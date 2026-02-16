<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    private static int $yearSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = 2000 + (self::$yearSequence++);
        $endYear = $startYear + 1;

        return [
            'name' => "{$startYear}/{$endYear}",
            'start_date' => "{$startYear}-09-01",
            'end_date' => "{$endYear}-06-30",
            'is_current' => false,
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
