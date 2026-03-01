<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'content' => $this->faker->sentence().'?',
            'type' => $this->faker->randomElement(['multiple', 'one_choice', 'boolean', 'text']),
            'points' => $this->faker->numberBetween(1, 20),
            'order_index' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Question requiring a file upload as the student's answer.
     */
    public function fileType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
        ]);
    }
}
