<?php

namespace Tests\Traits;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use App\Models\User;

trait CreatesTestExams
{
    protected function createExamWithQuestions(?User $teacher = null, array $examAttributes = [], int $questionCount = 3): Exam
    {
        $teacher = $teacher ?? $this->createTeacher();

        /** @var Exam $exam */
        $exam = Exam::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $examAttributes));

        for ($i = 1; $i <= $questionCount; $i++) {
            $question = Question::factory()->create([
                'exam_id' => $exam->id,
                'type' => 'one_choice',
                'points' => 10,
                'order_index' => $i,
            ]);

            Choice::factory()->count(4)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);

            Choice::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);
        }

        return $exam->load('questions.choices');
    }

    protected function createTextQuestionExam(?User $teacher = null, array $examAttributes = []): Exam
    {
        $teacher = $teacher ?? $this->createTeacher();

        /** @var Exam $exam */
        $exam = Exam::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $examAttributes));

        Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'points' => 10,
            'order_index' => 1,
        ]);

        return $exam->load('questions');
    }

    protected function createMultipleChoiceQuestionExam(?User $teacher = null, array $examAttributes = []): Exam
    {
        $teacher = $teacher ?? $this->createTeacher();

        /** @var Exam $exam */
        $exam = Exam::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $examAttributes));

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'multiple',
            'points' => 10,
            'order_index' => 1,
        ]);

        Choice::factory()->count(2)->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        Choice::factory()->count(2)->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        return $exam->load('questions.choices');
    }

    protected function createQuestionForExam(Exam $exam, string $type = 'one_choice', array $attributes = []): Question
    {
        /** @var Question $question */
        $question = Question::factory()->create(array_merge([
            'exam_id' => $exam->id,
            'type' => $type,
            'points' => 10,
        ], $attributes));

        if (in_array($type, ['one_choice', 'boolean', 'multiple'])) {
            $correctCount = $type === 'multiple' ? 2 : 1;

            Choice::factory()->count($correctCount)->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);

            Choice::factory()->count(4 - $correctCount)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);
        }

        return $question->load('choices');
    }
}
