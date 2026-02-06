<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class resource with context-aware fields
 *
 * Supports different contexts (admin, teacher, student, full) to return
 * appropriate data based on the requesting user's role and needs.
 *
 * @property \App\Models\ClassModel $resource
 */
class ClassResource extends JsonResource
{
  protected string $context = 'full';

  /**
   * Transform the resource into an array.
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'max_students' => $this->when(
        $this->shouldIncludeField('max_students'),
        $this->max_students
      ),

      'level' => $this->whenLoaded('level', fn() => [
        'id' => $this->level->id,
        'name' => $this->level->name,
      ]),

      'academic_year' => $this->whenLoaded('academicYear', fn() => [
        'id' => $this->academicYear->id,
        'name' => $this->academicYear->name,
        'is_current' => $this->academicYear->is_current,
      ]),

      'active_enrollments_count' => $this->when(
        isset($this->active_enrollments_count),
        $this->active_enrollments_count
      ),

      'subjects_count' => $this->when(
        isset($this->subjects_count),
        $this->subjects_count
      ),

      'class_subjects' => $this->when(
        $this->shouldIncludeField('class_subjects'),
        fn() => ClassSubjectResource::collection($this->whenLoaded('classSubjects'))
      ),

      'created_at' => $this->created_at?->toISOString(),
      'updated_at' => $this->updated_at?->toISOString(),
    ];
  }

  /**
   * Set context for conditional field inclusion
   */
  public function withContext(string $context): self
  {
    $this->context = $context;

    return $this;
  }

  /**
   * Determine if a field should be included based on context
   */
  protected function shouldIncludeField(string $field): bool
  {
    $fieldContextMap = [
      'max_students' => ['admin', 'full'],
      'class_subjects' => ['teacher', 'full'],
    ];

    if (! isset($fieldContextMap[$field])) {
      return true;
    }

    return in_array($this->context, $fieldContextMap[$field]);
  }
}
