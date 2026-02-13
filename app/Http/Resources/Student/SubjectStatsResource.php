<?php

namespace App\Http\Resources\Student;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subject Statistics Resource
 *
 * Transforms ClassSubject model into subject statistics for students.
 * Computes grades from eager-loaded relationships to avoid N+1 queries.
 *
 * @property \App\Models\ClassSubject $resource
 */
class SubjectStatsResource extends JsonResource
{
    private ?User $student = null;

    /**
     * @return $this
     */
    public function forStudent(User $student): static
    {
        $this->student = $student;

        return $this;
    }

    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'class_subject_id' => $this->resource->id,
            'subject_name' => $this->resource->subject?->name ?? '',
            'teacher_name' => $this->resource->teacher?->name ?? '',
            'coefficient' => $this->resource->coefficient,
            'average' => $this->calculateAverage(),
            'assessments_count' => $this->getAssessmentsCount(),
            'completed_count' => $this->getCompletedCount(),
        ];
    }

    /**
     * Calculate subject grade from eager-loaded assessments and assignments.
     *
     * Formula: Note = SUM(coef_assessment * normalized_score) / SUM(coef_assessment)
     * Where normalized_score = (score / max_points) * 20
     */
    private function calculateAverage(): ?float
    {
        if (! $this->student) {
            return null;
        }

        $totalWeightedScore = 0;
        $totalCoefficients = 0;

        foreach ($this->resource->assessments as $assessment) {
            $assignment = $assessment->assignments->first();

            if (! $assignment || $assignment->score === null) {
                continue;
            }

            $maxPoints = $assessment->questions->sum('points');

            if ($maxPoints <= 0) {
                continue;
            }

            $normalizedScore = ($assignment->score / $maxPoints) * 20;
            $totalWeightedScore += $assessment->coefficient * $normalizedScore;
            $totalCoefficients += $assessment->coefficient;
        }

        return $totalCoefficients > 0 ? round($totalWeightedScore / $totalCoefficients, 2) : null;
    }

    /**
     * Get the total number of assessments for this subject.
     */
    private function getAssessmentsCount(): int
    {
        return $this->resource->assessments->count();
    }

    /**
     * Get the number of completed assessments.
     */
    private function getCompletedCount(): int
    {
        return $this->resource->assessments
            ->flatMap->assignments
            ->whereNotNull('score')
            ->count();
    }
}
