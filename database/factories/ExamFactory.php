<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('+1 hour', '+1 week');
        $endTime = (clone $startTime)->modify('+'.$this->faker->numberBetween(1, 5).' hours');

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'duration' => $this->faker->numberBetween(30, 180),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => $this->faker->boolean(80),
            'teacher_id' => User::factory(),
        ];
    }
}
