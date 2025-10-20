<?php

namespace Database\Factories;

use App\Models\ExamAssignment;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamAssignment>
 */
class ExamAssignmentFactory extends Factory
{
    protected $model = ExamAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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
            'status' => 'assigned',
            'teacher_notes' => null,
            'security_violation' => null,
            'forced_submission' => false,
        ];
    }

    /**
     * Set the assignment as just assigned
     */
    public function assigned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'assigned',
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'auto_score' => null,
            'teacher_notes' => null,
        ]);
    }

    /**
     * Set the assignment as started
     */
    public function started(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'started',
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

        return $this->state(fn(array $attributes) => [
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

        return $this->state(fn(array $attributes) => [
            'status' => 'graded',
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'auto_score' => $this->faker->randomFloat(2, 0, 20),
            'score' => $this->faker->randomFloat(2, 0, 20),
            'teacher_notes' => $this->faker->optional(0.7)->sentence(),
        ]);
    }
}
