<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to enrolled students when an assessment is published.
 */
class AssessmentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Assessment  $assessment  The published assessment
     */
    public function __construct(public readonly Assessment $assessment) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'assessment_published',
            'assessment_id' => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject' => $this->assessment->classSubject?->subject?->name,
            'scheduled_at' => $this->assessment->scheduled_at?->toIso8601String(),
            'delivery_mode' => $this->assessment->delivery_mode,
            'url' => route('student.assessments.show', $this->assessment->id),
        ];
    }
}
