<?php

namespace App\Strategies\Validation\Score;

use Illuminate\Validation\Validator;

class ScoreNotExceedsMaxValidationStrategy implements ScoreValidationStrategy
{
    public function validate(Validator $validator, array $data, array $context = []): void
    {
        $exam = $context['exam'] ?? null;
        $scores = $data['scores'] ?? [];

        if (! $exam || empty($scores)) {
            return;
        }

        foreach ($scores as $index => $scoreData) {
            if (! isset($scoreData['question_id']) || ! isset($scoreData['score'])) {
                continue;
            }

            $question = $exam->questions()
                ->where('id', $scoreData['question_id'])
                ->first();

            if ($question && $scoreData['score'] > $question->points) {
                $validator->errors()->add(
                    "scores.{$index}.score",
                    __('validation.custom.score.exceeds_max', ['max' => $question->points])
                );
            }
        }
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'score_not_exceeds_max';
    }
}
