<?php

namespace Database\Factories;

use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Choice>
 */
class ChoiceFactory extends Factory
{
    protected $model = Choice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'content' => $this->faker->sentence(3),
            'is_correct' => false,
            'order_index' => $this->faker->numberBetween(1, 5),
        ];
    }

    /**
     * Indicate that the choice is correct.
     */
    public function correct(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_correct' => true,
        ]);
    }

    /**
     * Indicate that the choice is incorrect.
     */
    public function incorrect(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_correct' => false,
        ]);
    }
}
