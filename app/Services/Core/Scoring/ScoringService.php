<?php

namespace App\Services\Core\Scoring;

use App\Contracts\Scoring\ScoringStrategyInterface;
use App\Models\AssessmentAssignment;
use App\Models\Question;
use App\Strategies\Scoring\BooleanScoringStrategy;
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
            'answers',
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
            'answers',
        ]);

        $autoCorrectableTypes = ['one_choice', 'multiple', 'boolean'];

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
     * @param  AssessmentAssignment  $assignment  The assignment to check
     * @return bool True if the exam has text/essay questions
     */
    public function hasManualCorrectionQuestions(AssessmentAssignment $assignment): bool
    {
        return $assignment->assessment->questions()
            ->whereIn('type', ['text', 'essay'])
            ->exists();
    }

    /**
     * Get the appropriate strategy for a question type.
     *
     * @param  string  $questionType  The type of question
     * @return ScoringStrategyInterface|null The matching strategy or null
     */
    private function getStrategyForQuestionType(string $questionType): ?ScoringStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($questionType)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Get all registered scoring strategies.
     *
     * @return array<ScoringStrategyInterface>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
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

        foreach ($scores as $scoreData) {
            $answers = $answersByQuestionId->get($scoreData['question_id'], collect());

            if ($answers->isNotEmpty()) {
                $answers->first()->update([
                    'score' => $scoreData['score'],
                    'feedback' => $scoreData['feedback'] ?? null,
                ]);

                $answers->skip(1)->each(function ($answer) use ($scoreData) {
                    $answer->update([
                        'score' => 0,
                        'feedback' => $scoreData['feedback'] ?? null,
                    ]);
                });

                $updatedCount++;
            }
        }

        $totalScore = $this->calculateAssignmentScore($assignment);

        $assignment->update([
            'score' => $totalScore,
            'teacher_notes' => $teacherNotes,
            'graded_at' => now(),
        ]);

        return [
            'updated_count' => $updatedCount,
            'total_score' => $totalScore,
            'status' => 'graded',
        ];
    }
}
