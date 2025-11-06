<?php

namespace App\Strategies\Validation\Score;

use App\Models\Question;
use Illuminate\Validation\Validator;

class SingleQuestionExistsValidationStrategy implements ScoreValidationStrategy
{
    public function validate(Validator $validator, array $data, array $context = []): void
    {
        if (! isset($data['exam_id']) || ! isset($data['question_id'])) {
            return;
        }

        $question = Question::where('id', $data['question_id'])
            ->where('exam_id', $data['exam_id'])
            ->first();

        if (! $question) {
            $validator->errors()->add(
                'question_id',
                __('validation.custom.question_id.not_in_exam')
            );

            return;
        }

        if (isset($data['score']) && $data['score'] > $question->points) {
            $validator->errors()->add(
                'score',
                __('validation.custom.score.exceeds_max', ['max' => $question->points])
            );
        }
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'single_question_exists';
    }
}
