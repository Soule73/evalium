<?php

namespace App\Services\Teacher;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GradingQueryService
{
    /**
     * Get assignments for grading with pagination.
     */
    public function getAssignmentsForGrading(Assessment $assessment, int $perPage): LengthAwarePaginator
    {
        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->with(['student', 'answers.question'])
            ->paginate($perPage);
    }

    /**
     * Get assignment for a specific student with full answer details.
     */
    public function getAssignmentForStudent(Assessment $assessment, User $student): AssessmentAssignment
    {
        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->with(['answers.question', 'answers.choice'])
            ->firstOrFail();
    }

    /**
     * Load assessment relationships for grading index.
     */
    public function loadAssessmentForGradingIndex(Assessment $assessment): Assessment
    {
        return $assessment->load([
            'classSubject.class',
            'questions',
        ]);
    }

    /**
     * Load assessment relationships for grading show.
     */
    public function loadAssessmentForGradingShow(Assessment $assessment): Assessment
    {
        return $assessment->load(['classSubject.class', 'questions.choices']);
    }

    /**
     * Transform user answers from assignment for display.
     */
    public function transformUserAnswers(AssessmentAssignment $assignment): array
    {
        $userAnswers = [];

        foreach ($assignment->answers->groupBy('question_id') as $questionId => $answers) {
            if ($answers->count() === 1) {
                $userAnswers[$questionId] = $answers->first();
            } else {
                $firstAnswer = $answers->first();
                $firstAnswer->choices = $answers->filter(function ($answer) {
                    return $answer->choice_id !== null;
                })->map(function ($answer) {
                    return ['choice' => $answer->choice];
                })->values()->all();

                $userAnswers[$questionId] = $firstAnswer;
            }
        }

        return $userAnswers;
    }
}
