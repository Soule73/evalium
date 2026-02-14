<?php

namespace Database\Factories;

use App\Models\AssessmentAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssignmentAttachment>
 */
class AssignmentAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_assignment_id' => AssessmentAssignment::factory(),
            'file_name' => $this->faker->word().'.pdf',
            'file_path' => 'attachments/'.$this->faker->uuid().'.pdf',
            'file_size' => $this->faker->numberBetween(1024, 5242880),
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ];
    }
}
