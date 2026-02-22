<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to the teacher when a student submits an assessment.
 */
class AssessmentSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Assessment  $assessment  The submitted assessment
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
        $student = $this->assignment->student;

        return [
            'type' => 'assessment_submitted',
            'assessment_id' => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject' => $this->assessment->classSubject?->subject?->name,
            'assignment_id' => $this->assignment->id,
            'student_name' => $student?->name,
            'submitted_at' => $this->assignment->submitted_at?->toIso8601String(),
            'url' => route('teacher.assessments.review', [
                'assessment' => $this->assessment->id,
                'assignment' => $this->assignment->id,
            ]),
        ];
    }
}
