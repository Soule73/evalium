<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ClassSubject resource transformation
 *
 * @property \App\Models\ClassSubject $resource
 */
class ClassSubjectResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,

      'subject' => $this->whenLoaded('subject', fn() => [
        'id' => $this->subject->id,
        'name' => $this->subject->name,
        'code' => $this->subject->code,
      ]),

      'teacher' => $this->whenLoaded('teacher', fn() => [
        'id' => $this->teacher->id,
        'name' => $this->teacher->name,
        'email' => $this->teacher->email,
      ]),

      'class' => $this->whenLoaded('class', fn() => [
        'id' => $this->class->id,
        'name' => $this->class->name,
        'display_name' => $this->class->display_name,
      ]),

      'created_at' => $this->created_at?->toISOString(),
      'updated_at' => $this->updated_at?->toISOString(),
    ];
  }
}
