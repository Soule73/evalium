<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to the student when their assessment has been graded.
 */
class AssessmentGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Assessment  $assessment  The graded assessment
     * @param  AssessmentAssignment  $assignment  The student's assignment
     */
    public function __construct(
        public readonly Assessment $assessment,
        public readonly AssessmentAssignment $assignment
    ) {}

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
            'type' => 'assessment_graded',
            'assessment_id' => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject' => $this->assessment->classSubject?->subject?->name,
            'assignment_id' => $this->assignment->id,
            'url' => route('student.assessments.result', $this->assessment->id),
        ];
    }
}
