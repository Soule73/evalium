<?php

namespace App\Services\Core\Scoring;

use App\Contracts\Scoring\ScoringStrategyInterface;
use App\Strategies\Scoring\OneChoiceScoringStrategy;
use App\Strategies\Scoring\MultipleChoiceScoringStrategy;
use App\Strategies\Scoring\BooleanScoringStrategy;
use App\Strategies\Scoring\TextQuestionScoringStrategy;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Central scoring service orchestrating question scoring strategies.
 * 
 * Eliminates scoring logic duplication previously present in
 * ExamService, ExamSessionService, and ExamScoringService.
 * 
 * Design Pattern: Strategy Pattern
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
            new OneChoiceScoringStrategy(),
            new MultipleChoiceScoringStrategy(),
            new BooleanScoringStrategy(),
            new TextQuestionScoringStrategy(),
        ];
    }

    /**
     * Calculate the total score for an exam assignment.
     * 
     * Iterates through all questions and calculates the score for each.
     *
     * @param ExamAssignment $assignment The assignment to evaluate
     * @return float The total earned score
     */
    public function calculateAssignmentScore(ExamAssignment $assignment): float
    {
        $totalScore = 0.0;

        $exam = $assignment->exam()->with('questions.choices')->first();

        foreach ($exam->questions as $question) {
            $totalScore += $this->calculateQuestionScore($assignment, $question);
        }

        return round($totalScore, 2);
    }

    /**
     * Calculate the score for a specific question.
     *
     * @param ExamAssignment $assignment The assignment being evaluated
     * @param Question $question The question to score
     * @return float The earned score for this question
     */
    public function calculateQuestionScore(ExamAssignment $assignment, Question $question): float
    {
        $answers = $assignment->answers()
            ->where('question_id', $question->id)
            ->with('choice')
            ->get();

        $strategy = $this->getStrategyForQuestionType($question->type);

        if (!$strategy) {
            return 0.0;
        }

        return $strategy->calculateScore($question, $answers);
    }

    /**
     * Check if an answer is correct.
     *
     * @param Question $question The question being evaluated
     * @param Collection $answers The provided answers
     * @return bool True if the answer is correct
     */
    public function isAnswerCorrect(Question $question, Collection $answers): bool
    {
        $strategy = $this->getStrategyForQuestionType($question->type);

        if (!$strategy) {
            return false;
        }

        return $strategy->isCorrect($question, $answers);
    }

    /**
     * Calculate only auto-correctable score (MCQ, boolean).
     * 
     * Excludes text questions that require manual grading.
     *
     * @param ExamAssignment $assignment The assignment to evaluate
     * @return float The auto-correctable score
     */
    public function calculateAutoCorrectableScore(ExamAssignment $assignment): float
    {
        $totalScore = 0.0;

        $autoCorrectableTypes = ['one_choice', 'multiple', 'boolean'];

        $exam = $assignment->exam()->with('questions.choices')->first();

        foreach ($exam->questions as $question) {
            if (in_array($question->type, $autoCorrectableTypes)) {
                $totalScore += $this->calculateQuestionScore($assignment, $question);
            }
        }

        return round($totalScore, 2);
    }

    /**
     * Check if an exam contains questions requiring manual grading.
     *
     * @param ExamAssignment $assignment The assignment to check
     * @return bool True if the exam has text/essay questions
     */
    public function hasManualCorrectionQuestions(ExamAssignment $assignment): bool
    {
        return $assignment->exam->questions()
            ->whereIn('type', ['text', 'essay'])
            ->exists();
    }

    /**
     * Get the appropriate strategy for a question type.
     *
     * @param string $questionType The type of question
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
     * @param ScoringStrategyInterface $strategy The strategy to add
     * @return self Fluent interface
     */
    public function addStrategy(ScoringStrategyInterface $strategy): self
    {
        $this->strategies[] = $strategy;
        return $this;
    }
}
