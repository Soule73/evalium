<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Notifications\AssessmentStartingSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Sends starting-soon notifications to enrolled students for assessments
 * scheduled to begin within the next 15 minutes.
 *
 * Uses the reminder_sent_at column on assessments for deduplication,
 * ensuring each assessment triggers at most one reminder batch.
 *
 * Designed to run every 5 minutes via the scheduler.
 */
class SendAssessmentReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Send starting-soon notifications for assessments starting in ~15 minutes';

    /**
     * Find published, scheduled assessments starting within the next 15 minutes
     * that haven't already been reminded, and notify enrolled active students.
     */
    public function handle(): int
    {
        $windowEnd = now()->addMinutes(15);

        $assessments = Assessment::query()
            ->where('is_published', true)
            ->whereNotNull('scheduled_at')
            ->whereNull('reminder_sent_at')
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', $windowEnd)
            ->with('classSubject.class.enrollments.student')
            ->get();

        $this->info("Found {$assessments->count()} assessment(s) in reminder window.");

        foreach ($assessments as $assessment) {
            $activeStudents = $assessment->classSubject?->class?->enrollments
                ?->where('status', EnrollmentStatus::Active)
                ->map(fn ($e) => $e->student)
                ->filter()
                ->values() ?? collect();

            if ($activeStudents->isEmpty()) {
                $assessment->update(['reminder_sent_at' => now()]);

                continue;
            }

            Notification::send($activeStudents, new AssessmentStartingSoonNotification($assessment));

            $assessment->update(['reminder_sent_at' => now()]);

            $this->info("Notified {$activeStudents->count()} student(s) for assessment [{$assessment->id}] \"{$assessment->title}\".");
        }

        return self::SUCCESS;
    }
}
