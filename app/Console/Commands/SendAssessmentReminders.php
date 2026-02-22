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
 * Designed to run every 5 minutes via the scheduler.
 */
class SendAssessmentReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Send starting-soon notifications for assessments starting in ~15 minutes';

    /**
     * Find published, scheduled assessments starting in [13, 16] minutes
     * and notify enrolled active students (deduplicated via a notification flag).
     */
    public function handle(): int
    {
        $windowStart = now()->addMinutes(13);
        $windowEnd = now()->addMinutes(16);

        $assessments = Assessment::query()
            ->where('is_published', true)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
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
                continue;
            }

            Notification::send($activeStudents, new AssessmentStartingSoonNotification($assessment));

            $this->info("Notified {$activeStudents->count()} student(s) for assessment [{$assessment->id}] \"{$assessment->title}\".");
        }

        return self::SUCCESS;
    }
}
