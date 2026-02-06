<?php

namespace App\Services\Core;

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Question Duplication Service
 *
 * Single Responsibility: Duplicate questions between assessments
 */
class QuestionDuplicationService
{
    public function __construct(
        private readonly QuestionCrudService $questionCrudService,
    ) {}

    /**
     * Duplicate a single question to a new assessment
     */
    public function duplicateQuestion(Question $originalQuestion, Assessment $targetAssessment): Question
    {
        $questionData = $originalQuestion->toArray();
        unset($questionData['id'], $questionData['assessment_id'], $questionData['created_at'], $questionData['updated_at']);

        $newQuestion = $this->questionCrudService->createQuestion($targetAssessment, $questionData);

        $this->duplicateChoices($originalQuestion, $newQuestion);

        return $newQuestion;
    }

    /**
     * Duplicate multiple questions to a new assessment
     */
    public function duplicateMultiple(Collection $originalQuestions, Assessment $targetAssessment): Collection
    {
        $duplicatedQuestions = collect();

        foreach ($originalQuestions as $originalQuestion) {
            $duplicatedQuestions->push($this->duplicateQuestion($originalQuestion, $targetAssessment));
        }

        return $duplicatedQuestions;
    }

    /**
     * Duplicate choices from original question to new question
     */
    private function duplicateChoices(Question $originalQuestion, Question $newQuestion): void
    {
        foreach ($originalQuestion->choices as $originalChoice) {
            $choiceData = $originalChoice->toArray();
            unset($choiceData['id'], $choiceData['question_id'], $choiceData['created_at'], $choiceData['updated_at']);

            $newQuestion->choices()->create($choiceData);
        }
    }
}
