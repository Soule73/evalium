<?php

namespace Database\Factories;

use App\Enums\GradeReportStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GradeReport>
 */
class GradeReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'semester_id' => Semester::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'data' => [],
            'remarks' => null,
            'general_remark' => null,
            'rank' => null,
            'average' => null,
            'status' => GradeReportStatus::Draft,
            'validated_by' => null,
            'validated_at' => null,
            'file_path' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GradeReportStatus::Validated,
            'validated_by' => \App\Models\User::factory(),
            'validated_at' => now(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GradeReportStatus::Published,
            'validated_by' => \App\Models\User::factory(),
            'validated_at' => now()->subDay(),
        ]);
    }

    public function withAverage(float $average): static
    {
        return $this->state(fn (array $attributes) => [
            'average' => $average,
        ]);
    }

    public function withRank(int $rank): static
    {
        return $this->state(fn (array $attributes) => [
            'rank' => $rank,
        ]);
    }
}
