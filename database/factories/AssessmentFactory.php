<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Models\ClassSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(AssessmentType::cases());

        return [
            'class_subject_id' => ClassSubject::factory(),
            'teacher_id' => User::factory()->teacher(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $type,
            'delivery_mode' => DeliveryMode::defaultForType($type),
            'coefficient' => $this->faker->randomFloat(1, 1, 3),
            'duration_minutes' => $this->faker->optional()->numberBetween(30, 180),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'settings' => [],
        ];
    }

    public function devoir(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssessmentType::Homework,
            'delivery_mode' => DeliveryMode::Homework,
            'coefficient' => 1,
        ]);
    }

    public function examen(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssessmentType::Exam,
            'delivery_mode' => DeliveryMode::Supervised,
            'coefficient' => 2,
            'duration_minutes' => 120,
        ]);
    }

    public function tp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssessmentType::Practical,
            'delivery_mode' => DeliveryMode::Homework,
            'coefficient' => 1.5,
        ]);
    }

    public function supervised(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_mode' => DeliveryMode::Supervised,
            'duration_minutes' => $attributes['duration_minutes'] ?? 60,
        ]);
    }

    public function homework(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_mode' => DeliveryMode::Homework,
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
        ]);
    }

    public function withFileUploads(int $maxFiles = 3, int $maxSizeKb = 5120): static
    {
        return $this->state(fn (array $attributes) => [
            'max_files' => $maxFiles,
            'max_file_size' => $maxSizeKb,
            'allowed_extensions' => 'pdf,docx,zip',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }
}
