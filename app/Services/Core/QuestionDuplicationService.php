<?php

namespace App\Services\Core;

use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
        $originalQuestion->loadMissing('choices');

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
        if ($originalQuestions instanceof EloquentCollection) {
            $originalQuestions->loadMissing('choices');
        }

        $duplicatedQuestions = collect();

        foreach ($originalQuestions as $originalQuestion) {
            $duplicatedQuestions->push($this->duplicateQuestion($originalQuestion, $targetAssessment));
        }

        return $duplicatedQuestions;
    }

    /**
     * Duplicate choices from original question to new question using bulk insert
     */
    private function duplicateChoices(Question $originalQuestion, Question $newQuestion): void
    {
        $choicesData = $originalQuestion->choices->map(function ($originalChoice) use ($newQuestion) {
            $data = $originalChoice->toArray();
            unset($data['id'], $data['question_id'], $data['created_at'], $data['updated_at']);
            $data['question_id'] = $newQuestion->id;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            return $data;
        })->toArray();

        if (! empty($choicesData)) {
            Choice::insert($choicesData);
        }
    }
}
