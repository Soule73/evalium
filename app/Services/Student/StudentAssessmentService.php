<?php

namespace App\Services\Student;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
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
     * Get or create an assignment for a student
     *
     * @param  User  $student  The student
     * @param  Assessment  $assessment  The assessment
     * @return AssessmentAssignment The assignment
     */
    public function getOrCreateAssignment(User $student, Assessment $assessment): AssessmentAssignment
    {
        return AssessmentAssignment::firstOrCreate([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);
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
     * Auto-score non-text questions and set graded_at if all questions are auto-gradable
     *
     * @param  AssessmentAssignment  $assignment  The assignment
     * @param  Assessment  $assessment  The assessment with loaded questions and choices
     */
    public function autoScoreAssessment(AssessmentAssignment $assignment, Assessment $assessment): void
    {
        $assessment->load('questions.choices');

        $hasTextQuestions = $assessment->questions->contains('type', 'text');

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
        if (! $assignment || ! $assignment->exists) {
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

    /**
     * Get paginated assessments for a student with filtering
     *
     * @param  User  $student  The student
     * @param  int  $academicYearId  The academic year ID
     * @param  array  $filters  Filter parameters (status, search)
     * @param  int  $perPage  Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|array Paginated assignments or empty array
     */
    public function getStudentAssessmentsForIndex(User $student, int $academicYearId, array $filters, int $perPage)
    {
        $enrollment = $student->enrollments()
            ->where('status', 'active')
            ->whereHas('class', function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->with(['class.classSubjects'])
            ->first();

        if (! $enrollment) {
            return [];
        }

        $classSubjectIds = $enrollment->class->classSubjects->pluck('id');

        $assessmentsQuery = Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->with([
                'classSubject.class',
                'classSubject.subject',
                'classSubject.teacher',
                'questions',
            ])
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
            ->where('student_id', $student->id)
            ->with(['answers.question', 'answers.choice'])
            ->first();
    }
}
