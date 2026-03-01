<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\Student\StudentAssessmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Handles teacher-initiated exceptions for assessment disruptions.
 */
class AssignmentExceptionService
{
    public function __construct(
        private readonly StudentAssessmentService $studentAssessmentService
    ) {}

    /**
     * Reopen an interrupted supervised assignment for a student.
     *
     * Clears submitted_at while preserving started_at and recalculates remaining time.
     * Logs the action for audit purposes.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to reopen
     * @param  Assessment  $assessment  The parent assessment
     * @param  string|null  $reason  Teacher-provided reason for reopening
     * @return int Remaining seconds for the reopened assignment
     */
    public function reopenForStudent(
        AssessmentAssignment $assignment,
        Assessment $assessment,
        ?string $reason = null
    ): int {
        $remainingSeconds = $this->studentAssessmentService->calculateRemainingSeconds($assignment, $assessment) ?? 0;

        $assignment->update([
            'submitted_at' => null,
            'forced_submission' => false,
            'security_violation' => null,
        ]);

        Log::info('Assignment reopened by teacher', [
            'assignment_id' => $assignment->id,
            'assessment_id' => $assessment->id,
            'enrollment_id' => $assignment->enrollment_id,
            'teacher_id' => Auth::id(),
            'reason' => $reason,
            'remaining_seconds' => $remainingSeconds,
        ]);

        return $remainingSeconds;
    }

    /**
     * Reassign an assessment to a student who submitted no answers.
     *
     * Resets the assignment so the student can retake the assessment.
     * Only allowed for homework mode or supervised mode when the student
     * never started (questions not seen).
     *
     * @param  AssessmentAssignment  $assignment  The assignment to reassign
     * @param  Assessment  $assessment  The parent assessment
     * @param  string  $reason  Teacher-provided justification for reassignment
     */
    public function reassignForStudent(
        AssessmentAssignment $assignment,
        Assessment $assessment,
        string $reason
    ): void {
        $assignment->answers()->delete();

        $assignment->update([
            'started_at' => null,
            'submitted_at' => null,
            'graded_at' => null,
            'teacher_notes' => null,
            'forced_submission' => false,
            'security_violation' => null,
        ]);

        Log::info('Assignment reassigned by teacher', [
            'assignment_id' => $assignment->id,
            'assessment_id' => $assessment->id,
            'enrollment_id' => $assignment->enrollment_id,
            'teacher_id' => Auth::id(),
            'delivery_mode' => $assessment->delivery_mode->value,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if an assignment can be reopened.
     *
     * @return array{can_reopen: bool, reason: string|null}
     */
    public function canReopen(AssessmentAssignment $assignment, Assessment $assessment): array
    {
        if (! $assessment->isSupervisedMode()) {
            return ['can_reopen' => false, 'reason' => 'not_supervised'];
        }

        if (! $assignment->started_at) {
            return ['can_reopen' => false, 'reason' => 'not_started'];
        }

        $remainingSeconds = $this->studentAssessmentService->calculateRemainingSeconds($assignment, $assessment) ?? 0;

        if ($remainingSeconds <= 0) {
            return ['can_reopen' => false, 'reason' => 'time_fully_elapsed'];
        }

        if (! $assignment->submitted_at && ! $assignment->forced_submission) {
            return ['can_reopen' => false, 'reason' => 'not_interrupted'];
        }

        return ['can_reopen' => true, 'reason' => null];
    }
}
