<?php

namespace App\Services\Student;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AssessmentGradedNotification;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Traits\Paginatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * Find an existing assignment for a student without creating one.
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return AssessmentAssignment|null The assignment or null if not started
     */
    public function findAssignment(User $student, Assessment $assessment): ?AssessmentAssignment
    {
        $enrollment = $this->findActiveEnrollment($student, $assessment);

        if (! $enrollment) {
            return null;
        }

        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('enrollment_id', $enrollment->id)
            ->first();
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

        if ($assessment->allow_late_submission) {
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

        DB::transaction(function () use ($assignment, $answers) {
            $questionIds = array_keys($answers);
            $assignment->answers()->whereIn('question_id', $questionIds)->delete();

            $inserts = [];
            $now = now();

            foreach ($answers as $questionId => $value) {
                if (is_array($value)) {
                    foreach ($value as $choiceId) {
                        $inserts[] = [
                            'assessment_assignment_id' => $assignment->id,
                            'question_id' => $questionId,
                            'choice_id' => $choiceId,
                            'answer_text' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } elseif (is_int($value)) {
                    $inserts[] = [
                        'assessment_assignment_id' => $assignment->id,
                        'question_id' => $questionId,
                        'choice_id' => $value,
                        'answer_text' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    $inserts[] = [
                        'assessment_assignment_id' => $assignment->id,
                        'question_id' => $questionId,
                        'choice_id' => null,
                        'answer_text' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($inserts)) {
                Answer::insert($inserts);
            }
        });

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
            ->filter(fn ($q) => ! $q->type->requiresManualGrading())
            ->keyBy('id');

        if ($autoScorableQuestions->isEmpty()) {
            return;
        }

        $answersByQuestionId = $assignment->answers
            ->whereIn('question_id', $autoScorableQuestions->keys())
            ->groupBy('question_id');

        $scoreUpdates = [];

        foreach ($autoScorableQuestions as $questionId => $question) {
            $answers = $answersByQuestionId->get($questionId, collect());

            if ($answers->isEmpty()) {
                continue;
            }

            $score = $this->scoringService->calculateScoreForQuestion($question, $answers);

            $scoreUpdates[$answers->first()->id] = $score;

            $answers->skip(1)->each(function ($answer) use (&$scoreUpdates) {
                $scoreUpdates[$answer->id] = 0;
            });
        }

        if (! empty($scoreUpdates)) {
            $now = now()->toDateTimeString();
            $cases = collect($scoreUpdates)
                ->map(fn ($score, $id) => "WHEN id = {$id} THEN {$score}")
                ->implode(' ');
            $ids = implode(',', array_keys($scoreUpdates));
            DB::statement("UPDATE answers SET score = CASE {$cases} END, updated_at = '{$now}' WHERE id IN ({$ids})");
        }

        $hasTextQuestions = $assessment->questions->contains(fn ($q) => $q->type->requiresManualGrading());

        if (! $hasTextQuestions) {
            $assignment->update([
                'graded_at' => now(),
            ]);

            $assignment->loadMissing(['enrollment.student', 'assessment.classSubject.subject']);

            $student = $assignment->student;

            if ($student) {
                $student->notify(new AssessmentGradedNotification($assignment->assessment, $assignment));
            }
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
     * Get assessments for a student with assignments and status
     *
     * @param  User  $student  The student
     * @param  Collection  $assessments  Collection of assessments
     * @param  Enrollment|null  $enrollment  The student's active enrollment
     * @return Collection Collection of assignments with assessment and status
     */
    public function getAssessmentsWithAssignments(User $student, Collection $assessments, ?Enrollment $enrollment = null): Collection
    {
        $assessmentIds = $assessments->pluck('id');

        $assignments = AssessmentAssignment::whereIn('assessment_id', $assessmentIds)
            ->forStudent($student)
            ->get()
            ->keyBy('assessment_id');

        if (! $enrollment) {
            $enrollment = $student->enrollments()
                ->where('status', 'active')
                ->whereHas('class.classSubjects.assessments', fn ($q) => $q->whereIn('id', $assessmentIds))
                ->first();
        }

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
                $userAnswers[$questionId] = $answersForQuestion->first()->toArray();
            } else {
                $answerArray = $answersForQuestion->first()->toArray();
                $answerArray['choices'] = $answersForQuestion->filter(function ($answer) {
                    return $answer->choice_id !== null;
                })->map(function ($answer) {
                    return ['choice' => $answer->choice ? $answer->choice->toArray() : null];
                })->values()->all();

                $userAnswers[$questionId] = $answerArray;
            }
        }

        return $userAnswers;
    }

    /**
     * Get paginated assessments for a student with filtering
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  The academic year ID
     * @param  array  $filters  Filter parameters (status, search)
     * @param  int  $perPage  Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|array Paginated assignments or empty array
     */
    public function getStudentAssessmentsForIndex(User $student, ?int $academicYearId, array $filters, int $perPage, $enrollment = null)
    {
        if (! $enrollment) {
            $enrollment = $student->enrollments()
                ->where('status', 'active')
                ->when($academicYearId, function ($query) use ($academicYearId) {
                    $query->whereHas('class', fn ($q) => $q->where('academic_year_id', $academicYearId));
                })
                ->with(['class.classSubjects'])
                ->first();
        } else {
            $enrollment->loadMissing('class.classSubjects');
        }

        if (! $enrollment) {
            return [
                'assignments' => [],
                'subjects' => [],
            ];
        }

        $classSubjects = $enrollment->class->classSubjects;
        $classSubjectIds = $classSubjects->pluck('id');

        $subjects = $classSubjects->load(['subject:id,name', 'teacher:id,name'])
            ->map(fn ($cs) => [
                'id' => $cs->id,
                'subject_name' => $cs->subject?->name ?? '-',
                'teacher_name' => $cs->teacher?->name ?? '-',
            ])
            ->values()
            ->all();

        $enrollmentId = $enrollment->id;

        $assessmentsQuery = Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->where('is_published', true)
            ->with([
                'classSubject:id,subject_id,teacher_id',
                'classSubject.subject:id,name',
                'classSubject.teacher:id,name',
            ])
            ->withCount('questions')
            ->when($filters['class_subject_id'] ?? null, function ($query, $classSubjectId) {
                return $query->where('class_subject_id', $classSubjectId);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($query, $status) use ($enrollmentId) {
                match ($status) {
                    'graded' => $query->whereHas('assignments', fn ($q) => $q->where('enrollment_id', $enrollmentId)->whereNotNull('graded_at')),
                    'submitted' => $query->whereHas('assignments', fn ($q) => $q->where('enrollment_id', $enrollmentId)->whereNotNull('submitted_at')->whereNull('graded_at')),
                    'in_progress' => $query->whereHas('assignments', fn ($q) => $q->where('enrollment_id', $enrollmentId)->whereNotNull('started_at')->whereNull('submitted_at')),
                    'not_started' => $query->whereDoesntHave('assignments', fn ($q) => $q->where('enrollment_id', $enrollmentId)->whereNotNull('started_at')),
                    default => null,
                };
            })
            ->orderBy('scheduled_at', 'asc');

        $assessments = $this->paginateQuery($assessmentsQuery, $perPage);

        $assessmentItems = $assessments instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($assessments->items())
            : $assessments;

        $assignments = $this->getAssessmentsWithAssignments($student, $assessmentItems, $enrollment);

        if ($assessments instanceof \Illuminate\Pagination\AbstractPaginator) {
            $assessments->setCollection($assignments);
        } else {
            $assessments = $assignments;
        }

        return [
            'assignments' => $assessments,
            'subjects' => $subjects,
        ];
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
        $enrollment = $this->findActiveEnrollment($student, $assessment);

        abort_if(! $enrollment, 404);

        return $enrollment;
    }

    /**
     * Find the student's active enrollment for the assessment's class.
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return Enrollment|null The active enrollment or null
     */
    private function findActiveEnrollment(User $student, Assessment $assessment): ?Enrollment
    {
        $assessment->loadMissing('classSubject');

        return Enrollment::where('student_id', $student->id)
            ->where('class_id', $assessment->classSubject->class_id)
            ->where('status', 'active')
            ->first();
    }
}
