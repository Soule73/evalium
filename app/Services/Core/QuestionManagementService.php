<?php

namespace App\Services\Core;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Question Management Service - Handle all question-related operations
 *
 * Single Responsibility: Manage questions and choices for assessments
 */
class QuestionManagementService
{
    /**
     * Create questions for an assessment
     */
    public function createQuestionsForAssessment(Assessment $assessment, array $questionsData): void
    {
        foreach ($questionsData as $questionData) {
            $question = $assessment->questions()->create([
                'content' => $questionData['content'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'order_index' => $questionData['order_index'] ?? 0,
            ]);

            $this->createChoicesForQuestion($question, $questionData);
        }
    }

    /**
     * Update questions for an assessment
     */
    public function updateQuestionsForAssessment(Assessment $assessment, array $questionsData): void
    {
        DB::transaction(function () use ($assessment, $questionsData) {
            foreach ($questionsData as $questionData) {
                if (isset($questionData['id']) && is_numeric($questionData['id']) && $questionData['id'] > 0) {
                    $this->updateQuestion($assessment, $questionData);
                } else {
                    $this->createSingleQuestion($assessment, $questionData);
                }
            }
        });
    }

    /**
     * Update a single question
     */
    private function updateQuestion(Assessment $assessment, array $questionData): void
    {
        Question::where('id', $questionData['id'])
            ->where('assessment_id', $assessment->id)
            ->update([
                'content' => $questionData['content'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'order_index' => $questionData['order_index'] ?? 0,
                'updated_at' => now(),
            ]);

        $question = Question::find($questionData['id']);
        if ($question) {
            $this->updateChoicesForQuestion($question, $questionData);
        }
    }

    /**
     * Create a single question
     */
    private function createSingleQuestion(Assessment $assessment, array $questionData): Question
    {
        $question = $assessment->questions()->create([
            'content' => $questionData['content'],
            'type' => $questionData['type'],
            'points' => $questionData['points'],
            'order_index' => $questionData['order_index'] ?? 0,
        ]);

        $this->createChoicesForQuestion($question, $questionData);

        return $question;
    }

    /**
     * Delete questions by IDs
     */
    public function deleteQuestionsById(Assessment $assessment, array $questionIds): void
    {
        $validQuestionIds = Question::where('assessment_id', $assessment->id)
            ->whereIn('id', $questionIds)
            ->pluck('id')
            ->toArray();

        if (! empty($validQuestionIds)) {
            Answer::whereIn('question_id', $validQuestionIds)->delete();
            Choice::whereIn('question_id', $validQuestionIds)->delete();
            Question::whereIn('id', $validQuestionIds)->delete();
        }
    }

    /**
     * Delete choices by IDs
     */
    public function deleteChoicesById(Assessment $assessment, array $choiceIds): void
    {
        $validChoiceIds = Choice::whereHas('question', function ($query) use ($assessment) {
            $query->where('assessment_id', $assessment->id);
        })
            ->whereIn('id', $choiceIds)
            ->pluck('id')
            ->toArray();

        if (! empty($validChoiceIds)) {
            Answer::whereIn('choice_id', $validChoiceIds)->delete();
            Choice::whereIn('id', $validChoiceIds)->delete();
        }
    }

    /**
     * Delete questions in bulk
     *
     * @param  Collection|array  $questionIds
     */
    public function deleteBulk($questionIds): void
    {
        if ($questionIds instanceof Collection) {
            $questionIds = $questionIds->toArray();
        }

        if (! empty($questionIds)) {
            Choice::whereIn('question_id', $questionIds)->delete();
            Answer::whereIn('question_id', $questionIds)->delete();
            Question::whereIn('id', $questionIds)->delete();
        }
    }

    /**
     * Duplicate a question to a new assessment
     */
    public function duplicateQuestion(Question $originalQuestion, Assessment $newAssessment): Question
    {
        $questionData = $originalQuestion->toArray();
        unset($questionData['id'], $questionData['assessment_id'], $questionData['created_at'], $questionData['updated_at']);

        $newQuestion = $newAssessment->questions()->create($questionData);

        foreach ($originalQuestion->choices as $originalChoice) {
            $choiceData = $originalChoice->toArray();
            unset($choiceData['id'], $choiceData['question_id'], $choiceData['created_at'], $choiceData['updated_at']);

            $newQuestion->choices()->create($choiceData);
        }

        return $newQuestion;
    }

    /**
     * Create choices for a question based on type
     */
    private function createChoicesForQuestion(Question $question, array $questionData): void
    {
        match ($questionData['type']) {
            'multiple', 'one_choice' => $this->createChoiceOptions($question, $questionData),
            'boolean' => $this->createBooleanOptions($question, $questionData),
            default => null,
        };
    }

    /**
     * Create choice options
     */
    private function createChoiceOptions(Question $question, array $questionData): void
    {
        if (! isset($questionData['choices']) || ! is_array($questionData['choices'])) {
            return;
        }

        $choicesToCreate = [];
        foreach ($questionData['choices'] as $index => $choiceData) {
            $choicesToCreate[] = [
                'question_id' => $question->id,
                'content' => $choiceData['content'],
                'is_correct' => (bool) ($choiceData['is_correct'] ?? false),
                'order_index' => $choiceData['order_index'] ?? $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($choicesToCreate)) {
            Choice::insert($choicesToCreate);
        }
    }

    /**
     * Create boolean options
     */
    private function createBooleanOptions(Question $question, array $questionData): void
    {
        $correctAnswer = 'true';

        if (isset($questionData['choices']) && is_array($questionData['choices'])) {
            foreach ($questionData['choices'] as $choice) {
                if (isset($choice['is_correct']) && $choice['is_correct']) {
                    $correctAnswer = $choice['content'] ?? 'true';
                    break;
                }
            }
        }

        $booleanChoices = [
            [
                'question_id' => $question->id,
                'content' => 'true',
                'is_correct' => $correctAnswer === 'true',
                'order_index' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => $question->id,
                'content' => 'false',
                'is_correct' => $correctAnswer === 'false',
                'order_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Choice::insert($booleanChoices);
    }

    /**
     * Update choices for an existing question
     */
    private function updateChoicesForQuestion(Question $question, array $questionData): void
    {
        match ($questionData['type']) {
            'multiple', 'one_choice' => $this->updateChoiceOptions($question, $questionData),
            'boolean' => $this->updateBooleanOptions($question, $questionData),
            'text' => $this->deleteAllChoices($question),
            default => null,
        };
    }

    /**
     * Update choice options for multiple/one choice questions
     */
    private function updateChoiceOptions(Question $question, array $questionData): void
    {
        if (! isset($questionData['choices']) || ! is_array($questionData['choices'])) {
            return;
        }

        $choicesToCreate = [];

        foreach ($questionData['choices'] as $index => $choiceData) {
            $isCorrect = (bool) ($choiceData['is_correct'] ?? false);

            if (isset($choiceData['id']) && is_numeric($choiceData['id']) && $choiceData['id'] > 0) {
                Choice::where('id', $choiceData['id'])
                    ->where('question_id', $question->id)
                    ->update([
                        'content' => $choiceData['content'],
                        'is_correct' => $isCorrect,
                        'order_index' => $choiceData['order_index'] ?? $index,
                        'updated_at' => now(),
                    ]);
            } else {
                $choicesToCreate[] = [
                    'question_id' => $question->id,
                    'content' => $choiceData['content'],
                    'is_correct' => $isCorrect,
                    'order_index' => $choiceData['order_index'] ?? $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($choicesToCreate)) {
            Choice::insert($choicesToCreate);
        }
    }

    /**
     * Update boolean options
     */
    private function updateBooleanOptions(Question $question, array $questionData): void
    {
        $correctAnswer = 'true';
        $submittedChoices = [];

        if (isset($questionData['choices']) && is_array($questionData['choices'])) {
            foreach ($questionData['choices'] as $choice) {
                $submittedChoices[$choice['content']] = $choice;
                if (isset($choice['is_correct']) && $choice['is_correct']) {
                    $correctAnswer = $choice['content'] ?? 'true';
                }
            }
        }

        $existingChoices = $question->choices()->get()->keyBy('content');

        foreach (['true', 'false'] as $value) {
            $orderIndex = isset($submittedChoices[$value]) ? ($submittedChoices[$value]['order_index'] ?? ($value === 'true' ? 0 : 1)) : ($value === 'true' ? 0 : 1);

            if ($existingChoices->has($value)) {
                $existingChoices->get($value)->update([
                    'is_correct' => $correctAnswer === $value,
                    'order_index' => $orderIndex,
                ]);
            } else {
                $question->choices()->create([
                    'content' => $value,
                    'is_correct' => $correctAnswer === $value,
                    'order_index' => $orderIndex,
                ]);
            }
        }

        $question->choices()
            ->whereNotIn('content', ['true', 'false'])
            ->delete();
    }

    /**
     * Delete all choices for a question
     */
    private function deleteAllChoices(Question $question): void
    {
        $choiceIds = $question->choices()->pluck('id')->toArray();

        if (! empty($choiceIds)) {
            Answer::whereIn('choice_id', $choiceIds)->delete();
            $question->choices()->delete();
        }
    }
}
