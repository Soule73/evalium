<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use Illuminate\Console\Command;

/**
 * Creates AssessmentAssignment rows for enrolled students who never opened a
 * published, ended assessment.
 *
 * This ensures every student who was enrolled at the time an assessment ended
 * has a gradeable row — even if they never started — so teachers can record
 * a mark (or a zero) without any ghost entries in the grading workflow.
 *
 * Scheduling: every 30 minutes (see routes/console.php).
 */
class MaterialiseAssessmentAssignments extends Command
{
    protected $signature = 'assessment:materialise-assignments
                            {--dry-run : Preview without persisting any changes}';

    protected $description = 'Create missing assignment rows for enrolled students on ended assessments.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry-run mode — no assignments will be created.');
        }

        $created = 0;
        $skipped = 0;

        Assessment::query()
            ->where('is_published', true)
            ->with(['classSubject'])
            ->cursor()
            ->filter(fn (Assessment $assessment) => $assessment->hasEnded())
            ->each(function (Assessment $assessment) use ($dryRun, &$created, &$skipped): void {
                [$c, $s] = $this->materialise($assessment, $dryRun);
                $created += $c;
                $skipped += $s;
            });

        $this->info("Done. Created: {$created} | Already existing: {$skipped}");

        return Command::SUCCESS;
    }

    /**
     * Ensure every active enrollment in the assessment's class has an assignment row.
     *
     * @return array{int, int} [$created, $skipped]
     */
    private function materialise(Assessment $assessment, bool $dryRun): array
    {
        $classId = $assessment->classSubject?->class_id;

        if (! $classId) {
            return [0, 0];
        }

        $enrollments = Enrollment::active()
            ->where('class_id', $classId)
            ->pluck('id');

        if ($enrollments->isEmpty()) {
            return [0, 0];
        }

        $existingEnrollmentIds = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->whereIn('enrollment_id', $enrollments)
            ->pluck('enrollment_id')
            ->flip();

        $created = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollmentId) {
            if ($existingEnrollmentIds->has($enrollmentId)) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                AssessmentAssignment::create([
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $enrollmentId,
                ]);
            }

            $created++;

            $this->line("  + Assessment #{$assessment->id} — Enrollment #{$enrollmentId}");
        }

        return [$created, $skipped];
    }
}
