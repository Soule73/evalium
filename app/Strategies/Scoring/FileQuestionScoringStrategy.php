<?php

namespace App\Strategies\Scoring;

use App\Enums\QuestionType;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Scoring strategy for file-submission questions.
 *
 * File questions require manual grading by a teacher after reviewing
 * the uploaded file. The score is retrieved from the answer record
 * once the teacher has evaluated the submission.
 */
class FileQuestionScoringStrategy extends AbstractScoringStrategy
{
    protected array $supportedTypes = [QuestionType::File];

    /**
     * Return the manually assigned score for a file submission.
     *
     * Returns zero if the teacher has not yet graded the answer.
     *
     * @param  Question  $question  The file question being evaluated
     * @param  Collection  $answers  The student's answer containing file metadata
     * @return float The assigned score or 0.0 if not yet graded
     */
    public function calculateScore(Question $question, Collection $answers): float
    {
        if ($answers->isEmpty()) {
            return 0.0;
        }

        return $answers->first()->score ?? 0.0;
    }

    /**
     * Determine whether the file submission has been graded with a positive score.
     *
     * @param  Question  $question  The file question being evaluated
     * @param  Collection  $answers  The student's answer
     * @return bool True if the teacher assigned a score greater than zero
     */
    public function isCorrect(Question $question, Collection $answers): bool
    {
        if ($answers->isEmpty()) {
            return false;
        }

        $score = $answers->first()->score;

        return isset($score) && $score > 0;
    }

    /**
     * @return string Strategy description
     */
    public function getDescription(): string
    {
        return 'File submission question requiring manual grading. Score is assigned by the teacher after reviewing the uploaded file.';
    }
}
