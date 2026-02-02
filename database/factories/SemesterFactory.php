<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderNumber = $this->faker->numberBetween(1, 2);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $orderNumber === 1 ? 'Semestre 1' : 'Semestre 2',
            'start_date' => $orderNumber === 1 ? '2025-09-01' : '2026-02-01',
            'end_date' => $orderNumber === 1 ? '2026-01-31' : '2026-06-30',
            'order_number' => $orderNumber,
        ];
    }

    public function firstSemester(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Semestre 1',
            'order_number' => 1,
        ]);
    }

    public function secondSemester(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Semestre 2',
            'order_number' => 2,
        ]);
    }
}
