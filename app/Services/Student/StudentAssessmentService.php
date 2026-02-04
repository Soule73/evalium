<?php

namespace App\Services\Student;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Support\Collection;

/**
 * Student Assessment Service
 *
 * Handles business logic for student assessment operations.
 * Single Responsibility: Manage student assessment lifecycle and scoring.
 */
class StudentAssessmentService
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Get or create an assignment for a student
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @param  bool  $startNow  Whether to set started_at to now
     * @return AssessmentAssignment The assignment
     */
    public function getOrCreateAssignment(User $student, Assessment $assessment, bool $startNow = false): AssessmentAssignment
    {
        $attributes = [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ];

        $defaults = [
            'assigned_at' => now(),
        ];

        if ($startNow) {
            $defaults['started_at'] = now();
        }

        return AssessmentAssignment::firstOrCreate($attributes, $defaults);
    }

    /**
     * Start an assessment (set started_at if not already set)
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @return AssessmentAssignment Updated assignment
     */
    public function startAssessment(AssessmentAssignment $assignment): AssessmentAssignment
    {
        if (! $assignment->started_at) {
            $assignment->update(['started_at' => now()]);
        }

        return $assignment->fresh();
    }

    /**
     * Save student answers for an assessment
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  array  $answers  The answers array (question_id => value)
     * @return bool Success status
     */
    public function saveAnswers(AssessmentAssignment $assignment, array $answers): bool
    {
        if ($assignment->submitted_at) {
            return false;
        }

        foreach ($answers as $questionId => $value) {
            $assignment->answers()->where('question_id', $questionId)->delete();

            if (is_array($value)) {
                foreach ($value as $choiceId) {
                    $assignment->answers()->create([
                        'question_id' => $questionId,
                        'choice_id' => $choiceId,
                    ]);
                }
            } elseif (is_numeric($value)) {
                $assignment->answers()->create([
                    'question_id' => $questionId,
                    'choice_id' => $value,
                ]);
            } else {
                $assignment->answers()->create([
                    'question_id' => $questionId,
                    'answer_text' => $value,
                ]);
            }
        }

        return true;
    }

    /**
     * Submit an assessment with answers
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @param  array  $answers  The answers array
     * @return bool Success status
     */
    public function submitAssessment(AssessmentAssignment $assignment, Assessment $assessment, array $answers): bool
    {
        if ($assignment->submitted_at) {
            return false;
        }

        $this->saveAnswers($assignment, $answers);

        $this->autoScoreAssessment($assignment, $assessment);

        $assignment->update(['submitted_at' => now()]);

        return true;
    }

    /**
     * Auto-score non-text questions
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment with loaded questions and choices
     */
    public function autoScoreAssessment(AssessmentAssignment $assignment, Assessment $assessment): void
    {
        $assessment->load('questions.choices');

        foreach ($assessment->questions as $question) {
            if ($question->type === 'text') {
                continue;
            }

            $answers = $assignment->answers()->where('question_id', $question->id)->get();

            if ($answers->isEmpty()) {
                continue;
            }

            $score = $this->scoringService->calculateQuestionScore($assignment, $question);

            $answers->first()->update(['score' => $score]);

            $answers->skip(1)->each(function ($answer) {
                $answer->update(['score' => 0]);
            });
        }
    }

    /**
     * Determine assessment status based on assignment state
     *
     * @param  AssessmentAssignment|null  $assignment  The assignment (null if not started)
     * @return string Status: 'not_started', 'in_progress', 'completed'
     */
    public function getAssessmentStatus(?AssessmentAssignment $assignment): string
    {
        if (! $assignment) {
            return 'not_started';
        }

        if ($assignment->submitted_at) {
            return 'completed';
        }

        if ($assignment->started_at) {
            return 'in_progress';
        }

        return 'not_started';
    }

    /**
     * Validate if student can access an assessment
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return bool True if student has access
     */
    public function canStudentAccessAssessment(User $student, Assessment $assessment): bool
    {
        return $student->enrollments()
            ->where('status', 'active')
            ->whereHas('class.classSubjects', function ($query) use ($assessment) {
                $query->where('id', $assessment->class_subject_id);
            })
            ->exists();
    }

    /**
     * Get assessments for a student with assignments and status
     *
     * @param  User  $student  The student
     * @param  Collection  $assessments  Collection of assessments
     * @return Collection Collection of assignments with assessment and status
     */
    public function getAssessmentsWithAssignments(User $student, Collection $assessments): Collection
    {
        return $assessments->map(function ($assessment) use ($student) {
            $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->first();

            if (! $assignment) {
                $assignment = new AssessmentAssignment([
                    'assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                ]);
            }

            $assignment->assessment = $assessment;
            $assignment->status = $this->getAssessmentStatus($assignment);

            return $assignment;
        });
    }

    /**
     * Group and format user answers for display
     *
     * @param  Collection  $answers  Collection of answers
     * @return array Formatted answers grouped by question_id
     */
    public function formatUserAnswers(Collection $answers): array
    {
        $userAnswers = [];

        foreach ($answers->groupBy('question_id') as $questionId => $answersForQuestion) {
            if ($answersForQuestion->count() === 1) {
                $userAnswers[$questionId] = $answersForQuestion->first();
            } else {
                $firstAnswer = $answersForQuestion->first();
                $firstAnswer->choices = $answersForQuestion->filter(function ($answer) {
                    return $answer->choice_id !== null;
                })->map(function ($answer) {
                    return ['choice' => $answer->choice];
                })->values()->all();

                $userAnswers[$questionId] = $firstAnswer;
            }
        }

        return $userAnswers;
    }
}
