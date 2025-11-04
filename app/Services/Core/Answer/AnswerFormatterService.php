<?php

namespace App\Services\Core\Answer;

use App\Contracts\Answer\AnswerFormatterInterface;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Centralized service for exam answer formatting.
 *
 * 
 * Responsibilities:
 * - Format answers for frontend display
 * - Handle different answer types (single, multiple, text)
 * - Calculate completion statistics
 * - Provide answer management utilities
 * - Retrieve and format student results and review data
 */
class AnswerFormatterService implements AnswerFormatterInterface
{
    /**
     * Format assignment answers for frontend display.
     *
     * 
     * @param ExamAssignment $assignment The assignment containing the answers
     * @return array Formatted answers, grouped by question_id
     */
    public function formatForFrontend(ExamAssignment $assignment): array
    {
        $answers = $assignment->relationLoaded('answers')
            ? $assignment->answers
            : $assignment->answers()->with(['choice', 'question'])->get();

        return $answers
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                if ($questionAnswers->count() === 1) {
                    return $this->formatSingleAnswer($questionAnswers->first());
                }

                return $this->formatMultipleAnswers($questionAnswers);
            })
            ->toArray();
    }

    /**
     * Format a single answer (one_choice, boolean, text).
     * 
     * @param mixed $answer The Answer object
     * @return array Formatted answer with all metadata
     */
    public function formatSingleAnswer($answer): array
    {
        return [
            'type' => 'single',
            'question_id' => $answer->question_id,
            'choice_id' => $answer->choice_id,
            'answer_text' => $answer->answer_text,
            'choice' => $answer->choice,
            'score' => $answer->score,
            'feedback' => $answer->feedback,
        ];
    }

    /**
     * Format multiple answers (multiple choice questions).
     * 
     * @param Collection $answers Collection of answers for the same question
     * @return array Formatted answers as an array of choices
     */
    public function formatMultipleAnswers($answers): array
    {
        $firstAnswer = $answers->first();

        return [
            'type' => 'multiple',
            'question_id' => $firstAnswer->question_id,
            'choices' => $answers->map(function ($answer) {
                return [
                    'choice_id' => $answer->choice_id,
                    'choice' => $answer->choice,
                ];
            })->toArray(),
            'answer_text' => null,
            'score' => $firstAnswer->score,
            'feedback' => $firstAnswer->feedback,
        ];
    }

    /**
     * Check if an assignment has at least one answer.
     * 
     * @param ExamAssignment $assignment
     * @return bool True if the assignment has any answers
     */
    public function hasAnswers(ExamAssignment $assignment): bool
    {
        return $assignment->answers()->exists();
    }

    /**
     * Count the number of distinct answered questions.
     * 
     * @param ExamAssignment $assignment
     * @return int Number of questions with at least one answer
     */
    public function countAnsweredQuestions(ExamAssignment $assignment): int
    {
        return $assignment->answers()
            ->distinct('question_id')
            ->count('question_id');
    }

    /**
     * Get assignment completion statistics.
     * 
     * @param ExamAssignment $assignment
     * @return array Statistics including total, answered, and completion percentage
     */
    public function getCompletionStats(ExamAssignment $assignment): array
    {
        $exam = $assignment->relationLoaded('exam') ? $assignment->exam : $assignment->exam()->first();

        $totalQuestions = $exam->relationLoaded('questions')
            ? $exam->questions->count()
            : $exam->questions()->count();

        $answeredQuestions = $assignment->relationLoaded('answers')
            ? $assignment->answers->pluck('question_id')->unique()->count()
            : $assignment->answers()->distinct('question_id')->count('question_id');

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'unanswered_questions' => $totalQuestions - $answeredQuestions,
            'completion_percentage' => $totalQuestions > 0
                ? round(($answeredQuestions / $totalQuestions) * 100, 2)
                : 0,
            'is_complete' => $answeredQuestions === $totalQuestions,
        ];
    }

    /**
     * Get complete data for displaying student results.
     * 
     * 
     * @param ExamAssignment $assignment
     * @return array Formatted data with assignment, student, exam, and answers
     */
    public function getStudentResultsData(ExamAssignment $assignment): array
    {
        $assignment->load([
            'answers.question.choices',
            'answers.choice',
            'exam.questions.choices',
            'student'
        ]);

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $assignment->exam,
            'userAnswers' => $this->formatForFrontend($assignment),
            'stats' => $this->getCompletionStats($assignment),
        ];
    }

    /**
     * Get formatted data for displaying student results in a group context.
     *
     * @param Exam $exam Target exam
     * @param Group $group Student's group
     * @param User $student Target student
     * @return array Complete data for results display
     */
    public function getStudentResultsDataInGroup(Exam $exam, Group $group, User $student): array
    {
        $belongsToGroup = $group->students()
            ->where('student_id', $student->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$belongsToGroup) {
            abort(403, "Student does not belong to this group or is not active.");
        }

        $exam->load(['questions.choices', 'teacher']);
        $group->load('level');

        $assignment = $exam->assignments()
            ->with(['answers.choice'])
            ->where('student_id', $student->id)
            ->firstOrFail();

        $assignment->setRelation('student', $student);

        $questionsById = $exam->questions->keyBy('id');
        foreach ($assignment->answers as $answer) {
            if (isset($questionsById[$answer->question_id])) {
                $answer->setRelation('question', $questionsById[$answer->question_id]);
            }
        }

        $assignment->setRelation('exam', $exam);

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $assignment->exam,
            'group' => $group,
            'creator' => $exam->teacher,
            'userAnswers' => $this->formatForFrontend($assignment),
            'stats' => $this->getCompletionStats($assignment),
        ];
    }

    /**
     * Get formatted data for student review/correction page.
     *
     * @param Exam $exam Target exam
     * @param Group $group Student's group
     * @param User $student Target student
     * @return array Complete data for review page
     */
    public function getStudentReviewData(Exam $exam, Group $group, User $student): array
    {
        $belongsToGroup = $group->students()
            ->where('student_id', $student->id)->exists();

        if (!$belongsToGroup) {
            abort(403, "Student does not belong to this group.");
        }

        $exam->load('questions.choices');
        $group->load('level');

        $assignment = $exam->assignments()
            ->with([
                'answers.choice',
                'student'
            ])
            ->where('student_id', $student->id)
            ->firstOrFail();

        $assignment->setRelation('exam', $exam);

        if (!$assignment->relationLoaded('answers')) {
            $assignment->load(['answers.choice']);
        }

        $loadedExam = $assignment->exam;

        $questionsById = $loadedExam->questions->keyBy('id');

        foreach ($assignment->answers as $answer) {
            if (!$answer->relationLoaded('question') && isset($questionsById[$answer->question_id])) {
                $answer->setRelation('question', $questionsById[$answer->question_id]);
            }
        }

        return [
            'assignment' => $assignment,
            'student' => $assignment->student,
            'exam' => $loadedExam,
            'group' => $group,
            'questions' => $loadedExam->questions,
            'userAnswers' => $this->formatForFrontend($assignment),
            'totalQuestions' => $loadedExam->questions->count(),
            'totalPoints' => $loadedExam->questions->sum('points'),
        ];
    }

    /**
     * Prepare answer data based on question type.
     * 
     * Replaces ExamSessionService::prepareAnswerData()
     * 
     * @param string $questionType Question type (multiple, one_choice, boolean, text)
     * @param array $requestData Request data containing the answer
     * @return array Prepared data for insertion
     */
    public function prepareAnswerData(string $questionType, array $requestData): array
    {
        if (in_array($questionType, ['multiple', 'one_choice', 'boolean'])) {
            return [
                'choice_id' => $requestData['choice_id'] ?? null,
                'answer_text' => null,
            ];
        }

        return [
            'answer_text' => $requestData['answer_text'] ?? '',
            'choice_id' => null,
        ];
    }
}
