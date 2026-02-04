<?php

namespace App\Http\Resources;

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
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'coefficient' => $this->coefficient,
            'classSubject' => $this->whenLoaded('classSubject', function () {
                return [
                    'id' => $this->classSubject->id,
                    'class' => [
                        'id' => $this->classSubject->class->id,
                        'name' => $this->classSubject->class->name,
                    ],
                    'subject' => [
                        'id' => $this->classSubject->subject->id,
                        'name' => $this->classSubject->subject->name,
                    ],
                ];
            }),
        ];
    }
}
