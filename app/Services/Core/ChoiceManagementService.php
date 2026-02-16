<?php

namespace App\Services\Core;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;

/**
 * Choice Management Service
 *
 * Single Responsibility: Manage question choices (options) only
 */
class ChoiceManagementService
{
    /**
     * Create choices for a question based on its type
     */
    public function createChoicesForQuestion(Question $question, array $questionData): void
    {
        match ($questionData['type']) {
            'multiple', 'one_choice' => $this->createMultipleChoiceOptions($question, $questionData),
            'boolean' => $this->createBooleanOptions($question, $questionData),
            default => null,
        };
    }

    /**
     * Update choices for an existing question
     */
    public function updateChoicesForQuestion(Question $question, array $questionData): void
    {
        match ($questionData['type']) {
            'multiple', 'one_choice' => $this->updateMultipleChoiceOptions($question, $questionData),
            'boolean' => $this->updateBooleanOptions($question, $questionData),
            'text' => $this->deleteAllChoices($question),
            default => null,
        };
    }

    /**
     * Delete choices by IDs
     */
    public function deleteChoicesByIds(array $choiceIds): void
    {
        if (! empty($choiceIds)) {
            Answer::whereIn('choice_id', $choiceIds)->delete();
            Choice::whereIn('id', $choiceIds)->delete();
        }
    }

    /**
     * Delete all choices for a question
     */
    public function deleteAllChoices(Question $question): void
    {
        $choiceIds = $question->choices()->pluck('id')->toArray();

        if (! empty($choiceIds)) {
            Answer::whereIn('choice_id', $choiceIds)->delete();
            $question->choices()->delete();
        }
    }

    /**
     * Create multiple choice or single choice options
     */
    private function createMultipleChoiceOptions(Question $question, array $questionData): void
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
     * Create boolean (true/false) options
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
     * Update multiple choice or single choice options
     */
    private function updateMultipleChoiceOptions(Question $question, array $questionData): void
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
            $orderIndex = isset($submittedChoices[$value])
              ? ($submittedChoices[$value]['order_index'] ?? ($value === 'true' ? 0 : 1))
              : ($value === 'true' ? 0 : 1);

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
}
