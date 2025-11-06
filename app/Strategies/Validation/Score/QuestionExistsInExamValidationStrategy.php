<?php

namespace App\Strategies\Validation\Score;

use App\Models\Question;
use Illuminate\Validation\Validator;

class QuestionExistsInExamValidationStrategy implements ScoreValidationStrategy
{
    public function validate(Validator $validator, array $data, array $context = []): void
    {
        $exam = $context['exam'] ?? null;
        $scores = $data['scores'] ?? [];

        if (!$exam || empty($scores)) {
            return;
        }

        foreach ($scores as $index => $scoreData) {
            if (!isset($scoreData['question_id'])) {
                continue;
            }

            $question = $exam->questions()
                ->where('id', $scoreData['question_id'])
                ->first();

            if (!$question) {
                $validator->errors()->add(
                    "scores.{$index}.question_id",
                    __('validation.custom.question_id.not_in_exam')
                );
            }
        }
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'question_exists_in_exam';
    }
}
