<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamAssignment>
 */
class ExamAssignmentFactory extends Factory
{
    protected $model = ExamAssignment::class;

    public function definition(): array
    {
        $assignedAt = $this->faker->dateTimeBetween('-1 week', 'now');

        return [
            'exam_id' => Exam::factory(),
            'student_id' => User::factory(),
            'assigned_at' => $assignedAt,
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'status' => null,
            'teacher_notes' => null,
            'security_violation' => null,
            'forced_submission' => false,
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => null,
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'teacher_notes' => null,
        ]);
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => null,
            'started_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'teacher_notes' => null,
        ]);
    }

    /**
     * Set the assignment as submitted
     */
    public function submitted(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-4 hours', '-1 hour');
        $submittedAt = $this->faker->dateTimeBetween($startedAt, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'auto_score' => $this->faker->randomFloat(2, 0, 20),
            'score' => null,
            'teacher_notes' => null,
        ]);
    }

    /**
     * Set the assignment as graded
     */
    public function graded(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', '-2 hours');
        $submittedAt = $this->faker->dateTimeBetween($startedAt, '-1 hour');

        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'auto_score' => $this->faker->randomFloat(2, 0, 20),
            'score' => $this->faker->randomFloat(2, 0, 20),
            'teacher_notes' => $this->faker->optional(0.7)->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => null,
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'teacher_notes' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => null,
            'started_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'teacher_notes' => null,
        ]);
    }

    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-4 hours', '-1 hour');
        $submittedAt = $this->faker->dateTimeBetween($startedAt, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'auto_score' => $this->faker->randomFloat(2, 0, 20),
            'score' => $this->faker->randomFloat(2, 0, 20),
            'teacher_notes' => $this->faker->optional(0.5)->sentence(),
        ]);
    }
}
