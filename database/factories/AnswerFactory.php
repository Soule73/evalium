<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\AssessmentAssignment;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Answer>
 */
class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_assignment_id' => AssessmentAssignment::factory(),
            'question_id' => Question::factory(),
            'choice_id' => null,
            'answer_text' => $this->faker->optional()->sentence(),
            'score' => null,
            'feedback' => null,
        ];
    }

    /**
     * Answer with a choice (for multiple choice questions)
     */
    public function withChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'choice_id' => \App\Models\Choice::factory(),
            'answer_text' => null,
        ]);
    }

    /**
     * Answer with text (for text questions)
     */
    public function withText(): static
    {
        return $this->state(fn (array $attributes) => [
            'choice_id' => null,
            'answer_text' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Graded answer with score and feedback
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 0, 20),
            'feedback' => $this->faker->optional()->sentence(),
        ]);
    }
}
