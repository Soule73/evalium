<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'delivery_mode' => $this->delivery_mode?->value,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'classSubject' => $this->whenLoaded('classSubject', function () {
                return [
                    'class' => [
                        'name' => $this->classSubject->class->name ?? null,
                        'description' => $this->classSubject->class->description ?? null,
                        'level' => $this->classSubject->class->level ? [
                            'name' => $this->classSubject->class->level->name,
                        ] : null,
                        'academic_year' => $this->classSubject->class->academicYear ? [
                            'name' => $this->classSubject->class->academicYear->name,
                        ] : null,
                    ],
                    'subject' => [
                        'name' => $this->classSubject->subject->name ?? null,
                        'code' => $this->classSubject->subject->code ?? null,
                    ],
                ];
            }),
        ];
    }
}
