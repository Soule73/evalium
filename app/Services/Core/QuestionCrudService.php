<?php

namespace App\Services\Core;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Question CRUD Service
 *
 * Single Responsibility: Manage questions CRUD operations only
 */
class QuestionCrudService
{
    /**
     * Create multiple questions for an assessment
     */
    public function createQuestionsForAssessment(Assessment $assessment, array $questionsData): Collection
    {
        $questions = collect();

        foreach ($questionsData as $questionData) {
            $questions->push($this->createQuestion($assessment, $questionData));
        }

        return $questions;
    }

    /**
     * Create a single question
     */
    public function createQuestion(Assessment $assessment, array $questionData): Question
    {
        return $assessment->questions()->create([
            'content' => $questionData['content'],
            'type' => $questionData['type'],
            'points' => $questionData['points'],
            'order_index' => $questionData['order_index'] ?? 0,
        ]);
    }

    /**
     * Update a single question
     */
    public function updateQuestion(Question $question, array $questionData): Question
    {
        $question->update([
            'content' => $questionData['content'],
            'type' => $questionData['type'],
            'points' => $questionData['points'],
            'order_index' => $questionData['order_index'] ?? 0,
        ]);

        return $question->fresh();
    }

    /**
     * Update a question by ID within an assessment
     */
    public function updateQuestionById(Assessment $assessment, int $questionId, array $questionData): ?Question
    {
        Question::where('id', $questionId)
            ->where('assessment_id', $assessment->id)
            ->update([
                'content' => $questionData['content'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'order_index' => $questionData['order_index'] ?? 0,
                'updated_at' => now(),
            ]);

        return Question::find($questionId);
    }

    /**
     * Delete a single question
     */
    public function deleteQuestion(Question $question): void
    {
        Answer::where('question_id', $question->id)->delete();
        Choice::where('question_id', $question->id)->delete();
        $question->delete();
    }

    /**
     * Delete questions by IDs within an assessment
     */
    public function deleteQuestionsById(Assessment $assessment, array $questionIds): void
    {
        $validQuestionIds = Question::where('assessment_id', $assessment->id)
            ->whereIn('id', $questionIds)
            ->pluck('id')
            ->toArray();

        if (! empty($validQuestionIds)) {
            $this->deleteBulk($validQuestionIds);
        }
    }

    /**
     * Delete questions in bulk
     */
    public function deleteBulk(array|Collection $questionIds): void
    {
        if ($questionIds instanceof Collection) {
            $questionIds = $questionIds->toArray();
        }

        if (! empty($questionIds)) {
            Answer::whereIn('question_id', $questionIds)->delete();
            Choice::whereIn('question_id', $questionIds)->delete();
            Question::whereIn('id', $questionIds)->delete();
        }
    }
}
