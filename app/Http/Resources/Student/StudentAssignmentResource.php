<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Assignment Resource
 *
 * Unified structure for assessment assignments in student context.
 * Always includes normalized grade on /20 scale for consistency.
 */
class StudentAssignmentResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $assessment = $this->assessment;
    $maxPoints = $assessment?->questions?->sum('points') ?? 0;
    $normalizedGrade = null;

    if ($this->score !== null && $maxPoints > 0) {
      $normalizedGrade = round(($this->score / $maxPoints) * 20, 2);
    }

    return [
      'id' => $this->id,
      'assessment_id' => $this->assessment_id,
      'title' => $assessment?->title ?? '-',
      'type' => $assessment?->type,
      'subject_name' => $assessment?->classSubject?->subject?->name ?? '-',
      'class_name' => $assessment?->classSubject?->class?->name ?? '-',
      'teacher_name' => $assessment?->teacher?->name ?? '-',
      'duration_minutes' => $assessment?->duration_minutes,
      'coefficient' => $assessment?->coefficient,
      'raw_score' => $this->score,
      'max_points' => $maxPoints,
      'normalized_grade' => $normalizedGrade,
      'status' => $this->status,
      'submitted_at' => $this->submitted_at?->toISOString(),
      'graded_at' => $this->graded_at?->toISOString(),
      'created_at' => $this->created_at?->toISOString(),
    ];
  }
}
