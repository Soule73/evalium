<?php

namespace App\Strategies\Validation\Score;

/**
 * Question Exists In Assessment Validation Strategy
 *
 * Validates that each question in the scores array exists in the specified assessment
 */
class QuestionExistsInAssessmentValidationStrategy implements ScoreValidationStrategy
{
    public function validate($validator, array $data, array $context = []): void
    {
        if (! isset($data['scores']) || ! is_array($data['scores'])) {
            return;
        }

        $assessment = $context['assessment'] ?? null;

        if (! $assessment) {
            return;
        }

        $assessmentQuestionIds = $assessment->questions()->pluck('id')->toArray();

        foreach ($data['scores'] as $index => $score) {
            if (! isset($score['question_id'])) {
                continue;
            }

            if (! in_array($score['question_id'], $assessmentQuestionIds)) {
                $validator->errors()->add(
                    "scores.{$index}.question_id",
                    __('validation.question_not_in_assessment')
                );
            }
        }
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'question_exists_in_assessment';
    }
}
