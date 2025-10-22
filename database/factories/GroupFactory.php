<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');

        return [
            'level_id' => Level::inRandomOrder()->first()?->id ?? Level::factory()->create()->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_students' => $this->faker->numberBetween(15, 35),
            'is_active' => $this->faker->boolean(80),
            'academic_year' => $this->faker->randomElement(['2024-2025', '2025-2026']),
        ];
    }

    /**
     * Indicate that the group is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the group is for the current academic year.
     */
    public function currentYear(): static
    {
        $currentYear = Carbon::now()->year;
        $academicYear = Carbon::now()->month >= 9
            ? "{$currentYear}-" . ($currentYear + 1)
            : ($currentYear - 1) . "-{$currentYear}";

        return $this->state(fn(array $attributes) => [
            'academic_year' => $academicYear,
            'start_date' => Carbon::now()->startOfYear(),
            'end_date' => Carbon::now()->endOfYear(),
        ]);
    }
}
