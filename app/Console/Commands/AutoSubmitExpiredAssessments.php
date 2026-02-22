<?php

namespace App\Console\Commands;

use App\Enums\DeliveryMode;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\Student\StudentAssessmentService;
use Illuminate\Console\Command;

/**
 * Auto-submits supervised assessment assignments where the student's time has expired
 * or the global assessment window has closed, but the browser was closed/crashed
 * before a proper submission could be recorded.
 *
 * Scheduling: every 5 minutes (see routes/console.php).
 */
class AutoSubmitExpiredAssessments extends Command
{
    protected $signature = 'assessment:auto-submit-expired
                            {--dry-run : Preview without persisting any changes}';

    protected $description = 'Auto-submit in-progress supervised assignments whose time has expired.';

    public function __construct(private readonly StudentAssessmentService $studentAssessmentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry-run mode — no assignments will be submitted.');
        }

        $submitted = 0;
        $skipped = 0;

        AssessmentAssignment::query()
            ->whereNull('submitted_at')
            ->whereNotNull('started_at')
            ->whereHas(
                'assessment',
                fn ($q) => $q
                    ->where('is_published', true)
                    ->where('delivery_mode', DeliveryMode::Supervised->value)
            )
            ->with([
                'assessment.questions.choices',
                'answers.choice',
            ])
            ->cursor()
            ->each(function (AssessmentAssignment $assignment) use ($dryRun, &$submitted, &$skipped): void {
                $assessment = $assignment->assessment;

                if (! $this->shouldAutoSubmit($assignment, $assessment)) {
                    $skipped++;

                    return;
                }

                if (! $dryRun) {
                    $this->doSubmit($assignment, $assessment);
                }

                $submitted++;
            });

        $this->info("Done. Submitted: {$submitted} | Skipped (time not expired): {$skipped}");

        return Command::SUCCESS;
    }

    /**
     * Determine if an assignment should be auto-submitted.
     *
     * Returns true if per-student time is expired or the assessment window has globally closed.
     */
    private function shouldAutoSubmit(AssessmentAssignment $assignment, Assessment $assessment): bool
    {
        if ($this->studentAssessmentService->isTimeExpired($assignment, $assessment)) {
            return true;
        }

        return $assessment->hasEnded();
    }

    /**
     * Auto-score and force-submit the assignment.
     *
     * Uses the student's personal deadline when available (started_at + duration_minutes),
     * otherwise falls back to the assessment's global end time.
     */
    private function doSubmit(AssessmentAssignment $assignment, Assessment $assessment): void
    {
        $this->studentAssessmentService->autoScoreAssessment($assignment, $assessment);

        $submittedAt = $assignment->started_at && $assessment->duration_minutes
            ? $assignment->started_at->copy()->addMinutes($assessment->duration_minutes)
            : ($assessment->ends_at ?? now());

        $assignment->update([
            'submitted_at' => $submittedAt,
            'forced_submission' => true,
            'security_violation' => 'time_expired',
        ]);
    }
}
