<?php

namespace App\Services\Core\Scoring;

use App\Contracts\Scoring\ScoringStrategyInterface;
use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\AssessmentAssignment;
use App\Models\Question;
use App\Notifications\AssessmentGradedNotification;
use App\Strategies\Scoring\BooleanScoringStrategy;
use App\Strategies\Scoring\FileQuestionScoringStrategy;
use App\Strategies\Scoring\MultipleChoiceScoringStrategy;
use App\Strategies\Scoring\OneChoiceScoringStrategy;
use App\Strategies\Scoring\TextQuestionScoringStrategy;
use Illuminate\Support\Collection;

/**
 * Central scoring service orchestrating question scoring strategies.
 */
class ScoringService
{
    /**
     * @var array<ScoringStrategyInterface>
     */
    private array $strategies;

    /**
     * Initialize the service with all available scoring strategies.
     */
    public function __construct()
    {
        $this->strategies = [
            new OneChoiceScoringStrategy,
            new MultipleChoiceScoringStrategy,
            new BooleanScoringStrategy,
            new TextQuestionScoringStrategy,
            new FileQuestionScoringStrategy,
        ];
    }

    /**
     * Calculate the total score for an exam assignment.
     *
     *
     * @param  AssessmentAssignment  $assignment  The assignment to evaluate
     * @return float The total earned score
     */
    public function calculateAssignmentScore(AssessmentAssignment $assignment): float
    {
        $assignment->loadMissing([
            'assessment.questions.choices',
            'answers.choice',
        ]);

        $answersByQuestionId = $assignment->answers->groupBy('question_id');

        $totalScore = 0.0;

        foreach ($assignment->assessment->questions as $question) {
            $questionAnswers = $answersByQuestionId->get($question->id, collect());

            if ($questionAnswers->isEmpty()) {
                continue;
            }

            $strategy = $this->getStrategyForQuestionType($question->type);

            if (! $strategy) {
                continue;
            }

            $totalScore += $strategy->calculateScore($question, $questionAnswers);
        }

        return round($totalScore, 2);
    }

    /**
     * Check if an answer is correct.
     *
     * @param  Question  $question  The question being evaluated
     * @param  Collection  $answers  The provided answers
     * @return bool True if the answer is correct
     */
    public function isAnswerCorrect(Question $question, Collection $answers): bool
    {
        $strategy = $this->getStrategyForQuestionType($question->type);

        if (! $strategy) {
            return false;
        }

        return $strategy->isCorrect($question, $answers);
    }

    /**
     * Calculate only auto-correctable score (MCQ, boolean).
     *
     * Excludes text questions that require manual grading.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to evaluate
     * @return float The auto-correctable score
     */
    public function calculateAutoCorrectableScore(AssessmentAssignment $assignment): float
    {
        $assignment->loadMissing([
            'assessment.questions.choices',
            'answers.choice',
        ]);

        $autoCorrectableTypes = [QuestionType::OneChoice, QuestionType::Multiple, QuestionType::Boolean];

        $autoCorrectableQuestions = $assignment->assessment->questions
            ->whereIn('type', $autoCorrectableTypes)
            ->keyBy('id');

        if ($autoCorrectableQuestions->isEmpty()) {
            return 0.0;
        }

        $answersByQuestionId = $assignment->answers
            ->whereIn('question_id', $autoCorrectableQuestions->keys())
            ->groupBy('question_id');

        $totalScore = 0.0;

        foreach ($autoCorrectableQuestions as $questionId => $question) {
            $questionAnswers = $answersByQuestionId->get($questionId, collect());

            if ($questionAnswers->isEmpty()) {
                continue;
            }

            $strategy = $this->getStrategyForQuestionType($question->type);

            if (! $strategy) {
                continue;
            }

            $totalScore += $strategy->calculateScore($question, $questionAnswers);
        }

        return round($totalScore, 2);
    }

    /**
     * Check if an exam contains questions requiring manual grading.
     *
     * Covers both Text and File question types.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to check
     * @return bool True if the exam has manually graded questions
     */
    public function hasManualCorrectionQuestions(AssessmentAssignment $assignment): bool
    {
        $manualTypes = array_filter(
            QuestionType::cases(),
            fn (QuestionType $type) => $type->requiresManualGrading()
        );

        $manualValues = array_map(fn (QuestionType $type) => $type->value, $manualTypes);

        return $assignment->assessment->questions()
            ->whereIn('type', $manualValues)
            ->exists();
    }

    /**
     * Calculate the score for a single question given its answers.
     *
     * Returns 0.0 when no matching strategy exists.
     *
     * @param  Question  $question  The question to evaluate
     * @param  Collection  $answers  Answers for this question
     * @return float Earned score
     */
    public function calculateScoreForQuestion(Question $question, Collection $answers): float
    {
        $strategy = $this->getStrategyForQuestionType($question->type);

        if (! $strategy) {
            return 0.0;
        }

        return $strategy->calculateScore($question, $answers);
    }

    /**
     * Get the appropriate strategy for a question type.
     *
     * @param  QuestionType  $questionType  The type of question
     * @return ScoringStrategyInterface|null The matching strategy or null
     */
    private function getStrategyForQuestionType(QuestionType $questionType): ?ScoringStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($questionType)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Add a custom scoring strategy.
     *
     * Useful for adding new question types without modifying this file.
     *
     * @param  ScoringStrategyInterface  $strategy  The strategy to add
     * @return self Fluent interface
     */
    public function addStrategy(ScoringStrategyInterface $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    /**
     * Auto-grade an assignment with zero score for all questions.
     *
     * Used when a student has submitted no answers and the assessment has ended.
     * Sets graded_at and total score to 0, notifies the student.
     *
     * @return array{total_score: float, status: string}
     */
    public function autoGradeZero(AssessmentAssignment $assignment, ?string $teacherNotes = null): array
    {
        $assignment->update([
            'graded_at' => now(),
            'teacher_notes' => $teacherNotes,
        ]);

        $assignment->loadMissing(['enrollment.student', 'assessment.classSubject.subject']);

        $student = $assignment->student;

        if ($student) {
            $student->notify(new AssessmentGradedNotification($assignment->assessment, $assignment));
        }

        return [
            'total_score' => 0.0,
            'status' => 'graded',
        ];
    }

    /**
     * Save manual grades for multiple questions in an assignment.
     *
     * Handles batch update of question scores and feedback, then recalculates total score.
     *
     * @param  AssessmentAssignment  $assignment  The assignment to grade
     * @param  array  $scores  Array of scores: [['question_id' => 1, 'score' => 8.5, 'feedback' => '...'], ...]
     * @param  string|null  $teacherNotes  Optional teacher notes for the entire assignment
     * @return array Result with updated_count, total_score, and status
     */
    public function saveManualGrades(AssessmentAssignment $assignment, array $scores, ?string $teacherNotes = null): array
    {
        $assignment->loadMissing('answers');

        $answersByQuestionId = $assignment->answers->groupBy('question_id');

        $updatedCount = 0;
        $answerUpdates = [];

        foreach ($scores as $scoreData) {
            $answers = $answersByQuestionId->get($scoreData['question_id'], collect());

            if ($answers->isNotEmpty()) {
                $firstAnswer = $answers->first();
                $answerUpdates[] = [
                    'id' => $firstAnswer->id,
                    'assessment_assignment_id' => $firstAnswer->assessment_assignment_id,
                    'question_id' => $firstAnswer->question_id,
                    'score' => $scoreData['score'],
                    'feedback' => $scoreData['feedback'] ?? null,
                ];

                $answers->skip(1)->each(function ($answer) use ($scoreData, &$answerUpdates) {
                    $answerUpdates[] = [
                        'id' => $answer->id,
                        'assessment_assignment_id' => $answer->assessment_assignment_id,
                        'question_id' => $answer->question_id,
                        'score' => 0,
                        'feedback' => $scoreData['feedback'] ?? null,
                    ];
                });

                $updatedCount++;
            }
        }

        if (! empty($answerUpdates)) {
            Answer::upsert($answerUpdates, ['id'], ['score', 'feedback']);
        }

        $assignment->unsetRelation('answers');
        $totalScore = $this->calculateAssignmentScore($assignment);

        $assignment->update([
            'teacher_notes' => $teacherNotes,
            'graded_at' => now(),
        ]);

        $assignment->loadMissing(['enrollment.student', 'assessment.classSubject.subject']);

        $student = $assignment->student;

        if ($student) {
            $student->notify(new AssessmentGradedNotification($assignment->assessment, $assignment));
        }

        return [
            'updated_count' => $updatedCount,
            'total_score' => $totalScore,
            'status' => 'graded',
        ];
    }
}
