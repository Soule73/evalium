<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to enrolled students 15 minutes before a scheduled assessment starts.
 */
class AssessmentStartingSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Assessment  $assessment  The assessment starting soon
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
            'type' => 'assessment_starting_soon',
            'assessment_id' => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject' => $this->assessment->classSubject?->subject?->name,
            'scheduled_at' => $this->assessment->scheduled_at?->toIso8601String(),
            'delivery_mode' => (string) $this->assessment->delivery_mode,
            'url' => route('student.assessments.show', $this->assessment->id),
        ];
    }
}
