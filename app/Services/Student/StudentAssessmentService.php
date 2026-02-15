<?php

namespace App\Services\Student;

use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Traits\Paginatable;
use Illuminate\Support\Collection;

/**
 * Student Assessment Service
 *
 * Handles student assessment operations:
 * - Starting assessments
 * - Submitting answers
 * - Retrieving assessment data
 */
class StudentAssessmentService
{
    use Paginatable;

    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Get or create an assignment for a student (without starting the timer).
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return AssessmentAssignment The assignment
     */
    public function getOrCreateAssignment(User $student, Assessment $assessment): AssessmentAssignment
    {
        $enrollment = $this->resolveEnrollment($student, $assessment);

        return AssessmentAssignment::firstOrCreate([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);
    }

    /**
     * Start an assignment by setting started_at if not already set.
     *
     * Sets started_at for all delivery modes to enable consistent
     * status tracking (not_submitted -> in_progress -> submitted -> graded).
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @return AssessmentAssignment The updated assignment
     */
    public function startAssignment(AssessmentAssignment $assignment, Assessment $assessment): AssessmentAssignment
    {
        if (! $assignment->started_at) {
            $assignment->update(['started_at' => now()]);
            $assignment->refresh();
        }

        return $assignment;
    }

    /**     * Calculate the remaining seconds for a supervised assessment.
     *
     * Returns null for homework mode or when started_at is not set.
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @return int|null Remaining seconds, or null if not applicable
     */
    public function calculateRemainingSeconds(AssessmentAssignment $assignment, Assessment $assessment): ?int
    {
        if (! $assessment->isSupervisedMode() || ! $assignment->started_at || ! $assessment->duration_minutes) {
            return null;
        }

        $elapsed = $assignment->started_at->diffInSeconds(now());
        $totalSeconds = $assessment->duration_minutes * 60;
        $remaining = $totalSeconds - (int) $elapsed;

        return max(0, $remaining);
    }

    /**
     * Check if a supervised assessment's time has expired.
     *
     * Uses a configurable grace period for network latency tolerance.
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @param  bool  $withGrace  Whether to include grace period
     * @return bool True if time has expired
     */
    public function isTimeExpired(AssessmentAssignment $assignment, Assessment $assessment, bool $withGrace = false): bool
    {
        if (! $assessment->isSupervisedMode() || ! $assignment->started_at || ! $assessment->duration_minutes) {
            return false;
        }

        $gracePeriod = $withGrace ? (int) config('assessment.timing.grace_period_seconds', 30) : 0;
        $deadline = $assignment->started_at->copy()->addMinutes($assessment->duration_minutes)->addSeconds($gracePeriod);

        return now()->greaterThan($deadline);
    }

    /**
     * Check if the homework due date has passed.
     *
     * @param  Assessment  $assessment  The assessment
     * @return bool True if due date has passed and late submission is not allowed
     */
    public function isDueDatePassed(Assessment $assessment): bool
    {
        if (! $assessment->isHomeworkMode() || ! $assessment->due_date) {
            return false;
        }

        if ($assessment->settings['allow_late_submission'] ?? false) {
            return false;
        }

        return now()->greaterThan($assessment->due_date);
    }

    /**
     * Auto-submit an assignment if its supervised time has expired.
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @return bool True if auto-submitted, false if time remains or already submitted
     */
    public function autoSubmitIfExpired(AssessmentAssignment $assignment, Assessment $assessment): bool
    {
        if ($assignment->submitted_at) {
            return false;
        }

        if (! $this->isTimeExpired($assignment, $assessment)) {
            return false;
        }

        $this->autoScoreAssessment($assignment, $assessment);

        $assignment->update([
            'submitted_at' => $assignment->started_at->copy()->addMinutes($assessment->duration_minutes),
            'forced_submission' => true,
            'security_violation' => 'time_expired',
        ]);

        return true;
    }

    /**     * Save student answers for an assessment
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
     * Terminate an assessment due to security violation
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment
     * @param  string  $violationType  Type of violation (e.g., 'tab_switch', 'fullscreen_exit')
     * @param  string|null  $violationDetails  Additional details about the violation
     * @return bool Success status
     */
    public function terminateForViolation(
        AssessmentAssignment $assignment,
        Assessment $assessment,
        string $violationType,
        ?string $violationDetails = null
    ): bool {
        if (! $assessment->isSupervisedMode()) {
            return false;
        }

        if ($assignment->submitted_at) {
            return false;
        }

        $this->autoScoreAssessment($assignment, $assessment);

        $assignment->update([
            'submitted_at' => now(),
            'forced_submission' => true,
            'security_violation' => $violationType.($violationDetails ? ': '.$violationDetails : ''),
        ]);

        return true;
    }

    /**
     * Auto-score non-text questions and set graded_at if all questions are auto-gradable
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment with loaded questions and choices
     */
    public function autoScoreAssessment(AssessmentAssignment $assignment, Assessment $assessment): void
    {
        $assessment->loadMissing('questions.choices');
        $assignment->loadMissing('answers.choice');

        $autoScorableQuestions = $assessment->questions
            ->whereNotIn('type', [QuestionType::Text])
            ->keyBy('id');

        if ($autoScorableQuestions->isEmpty()) {
            return;
        }

        $answersByQuestionId = $assignment->answers
            ->whereIn('question_id', $autoScorableQuestions->keys())
            ->groupBy('question_id');

        foreach ($autoScorableQuestions as $questionId => $question) {
            $answers = $answersByQuestionId->get($questionId, collect());

            if ($answers->isEmpty()) {
                continue;
            }

            $strategy = $this->scoringService->getStrategies();
            $score = 0.0;

            foreach ($strategy as $scoringStrategy) {
                if ($scoringStrategy->supports($question->type)) {
                    $score = $scoringStrategy->calculateScore($question, $answers);
                    break;
                }
            }

            $answers->first()->update(['score' => $score]);

            $answers->skip(1)->each(function ($answer) {
                $answer->update(['score' => 0]);
            });
        }

        $hasTextQuestions = $assessment->questions->contains(fn ($q) => $q->type->requiresManualGrading());

        if (! $hasTextQuestions) {
            $totalScore = $this->scoringService->calculateAssignmentScore($assignment);
            $assignment->update([
                'score' => $totalScore,
                'graded_at' => now(),
            ]);
        }
    }

    /**
     * Determine assessment status based on assignment state
     *
     * @param  AssessmentAssignment|null  $assignment  The assignment (null if not submitted)
     * @return string Status: 'not_submitted', 'submitted', 'graded'
     */
    public function getAssessmentStatus(?AssessmentAssignment $assignment): string
    {
        if (! $assignment) {
            return 'not_submitted';
        }

        return $assignment->status;
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
        $assessmentIds = $assessments->pluck('id');

        $assignments = AssessmentAssignment::whereIn('assessment_id', $assessmentIds)
            ->forStudent($student)
            ->get()
            ->keyBy('assessment_id');

        $enrollment = $student->enrollments()
            ->where('status', 'active')
            ->whereHas('class.classSubjects.assessments', fn ($q) => $q->whereIn('id', $assessmentIds))
            ->first();

        return $assessments->map(function ($assessment) use ($enrollment, $assignments) {
            $assignment = $assignments->get($assessment->id);

            if (! $assignment) {
                $assignment = new AssessmentAssignment([
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $enrollment?->id,
                ]);
            }

            $assignment->assessment = $assessment;

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

    /**
     * Get paginated assessments for a student with filtering
     *
     * @param  User  $student  The student
     * @param  int  $academicYearId  The academic year ID
     * @param  array  $filters  Filter parameters (status, search)
     * @param  int  $perPage  Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|array Paginated assignments or empty array
     */
    public function getStudentAssessmentsForIndex(User $student, int $academicYearId, array $filters, int $perPage, $enrollment = null)
    {
        if (! $enrollment) {
            $enrollment = $student->enrollments()
                ->where('status', 'active')
                ->whereHas('class', function ($query) use ($academicYearId) {
                    $query->where('academic_year_id', $academicYearId);
                })
                ->with(['class.classSubjects'])
                ->first();
        } else {
            $enrollment->loadMissing('class.classSubjects');
        }

        if (! $enrollment) {
            return [];
        }

        $classSubjectIds = $enrollment->class->classSubjects->pluck('id');

        $assessmentsQuery = Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->with([
                'classSubject:id,subject_id,teacher_id',
                'classSubject.subject:id,name',
                'classSubject.teacher:id,name',
            ])
            ->withCount('questions')
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('scheduled_at', 'asc');

        $assessments = $this->simplePaginate($assessmentsQuery, $perPage);

        $assessmentItems = $assessments instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($assessments->items())
            : $assessments;

        $assignments = $this->getAssessmentsWithAssignments($student, $assessmentItems);

        if ($assessments instanceof \Illuminate\Pagination\AbstractPaginator) {
            $assessments->setCollection($assignments);
        } else {
            $assessments = $assignments;
        }

        if ($filters['status'] ?? null) {
            $assessments->setCollection(
                $assessments->getCollection()->filter(function ($assignment) use ($filters) {
                    return $assignment->status === $filters['status'];
                })->values()
            );
        }

        return $assessments;
    }

    /**
     * Get assessment assignment with results for a student
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return AssessmentAssignment|null The assignment with answers or null
     */
    public function getAssignmentForResults(User $student, Assessment $assessment): ?AssessmentAssignment
    {
        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->forStudent($student)
            ->with(['answers.question', 'answers.choice', 'enrollment'])
            ->first();
    }

    /**
     * Resolve the enrollment for a student in the assessment's class.
     */
    private function resolveEnrollment(User $student, Assessment $assessment): Enrollment
    {
        $assessment->loadMissing('classSubject');

        return Enrollment::where('student_id', $student->id)
            ->where('class_id', $assessment->classSubject->class_id)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
