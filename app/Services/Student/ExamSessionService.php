<?php

namespace App\Services\Student;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

class ExamSessionService
{

    public function findOrCreateAssignment(Exam $exam, User $student): ExamAssignment
    {
        return ExamAssignment::firstOrCreate([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ], [
            'status' => 'assigned',
        ]);
    }

    /**
     * Validate if the current time is within the exam's allowed timing.
     *
     * @param Exam $exam
     * @return bool
     */
    public function validateExamTiming(Exam $exam): bool
    {
        $now = Carbon::now();

        if ($exam->start_time && $exam->end_time) {
            return $now->between($exam->start_time, $exam->end_time);
        }

        return false;
    }

    public function startExam(ExamAssignment $assignment): void
    {
        if (in_array($assignment->status, ['assigned'])) {
            $assignment->update([
                'status' => 'started',
                'started_at' => Carbon::now(),
            ]);
        }
    }

    public function submitExam(ExamAssignment $assignment, ?float $autoScore, bool $hasTextQuestions = false, bool $isSecurityViolation = false): void
    {
        $submissionTime = Carbon::now();

        $finalStatus = ($hasTextQuestions || $isSecurityViolation) ? 'pending_review' : 'submitted';

        $assignment->update([
            'status' => $finalStatus,
            'submitted_at' => $submissionTime,
            'forced_submission' => $isSecurityViolation,
            'score' => ($hasTextQuestions || $isSecurityViolation) ? null : $autoScore,
            'auto_score' => $autoScore ?? $assignment->auto_score,
        ]);
    }

    /**
     * @param array<string, mixed> $answers
     */
    public function handleViolation(
        ExamAssignment $assignment,
        string $violationType,
    ): void {
        $submissionTime = Carbon::now();

        $assignment->update([
            'status' => 'pending_review',
            'submitted_at' => $submissionTime,
            'score' => null,
            'security_violation' => $violationType,
            'forced_submission' => true,
        ]);
    }


    public function calculateAutoScore(ExamAssignment $assignment): int
    {
        $totalScore = 0;

        $autoCorrectableQuestions = $assignment->exam->questions()
            ->whereIn('type', ['multiple', 'one_choice', 'boolean'])
            ->get();

        foreach ($autoCorrectableQuestions as $question) {
            $answer = Answer::where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();

            if ($answer && $answer->choice_id) {
                $isCorrect = $this->checkAnswerCorrectness($question, $answer);
                if ($isCorrect) {
                    $totalScore += $question->points;
                }
            }
        }

        return $totalScore;
    }

    private function checkAnswerCorrectness($question, $answer): bool
    {
        switch ($question->type) {
            case 'multiple':
            case 'one_choice':
            case 'boolean':
                if (!$answer->choice_id) {
                    return false;
                }

                $selectedChoice = $question->choices()->where('id', $answer->choice_id)->first();
                return $selectedChoice && $selectedChoice->is_correct;

            case 'text':
                return false;

            default:
                return false;
        }
    }

    public function saveAnswer(ExamAssignment $assignment, Question $question, array $data): void
    {
        if ($question->type === 'multiple') {
            Answer::create([
                'assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'choice_id' => $data['choice_id'],
                'answer_text' => null,
            ]);
        } else {
            Answer::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                ],
                $data
            );
        }
    }

    public function clearAnswersForQuestion(ExamAssignment $assignment, int $questionId): void
    {
        Answer::where('assignment_id', $assignment->id)
            ->where('question_id', $questionId)
            ->delete();
    }

    /**
     * @param array<int, mixed> $answers
     */
    public function saveMultipleAnswers(ExamAssignment $assignment, Exam $exam, array $answers): void
    {
        foreach ($answers as $questionId => $answer) {
            $question = $exam->questions()->find($questionId);
            if (!$question) continue;

            $this->clearAnswersForQuestion($assignment, $questionId);

            if ($question->type === 'multiple' && is_array($answer)) {
                foreach ($answer as $choiceId) {
                    Answer::create([
                        'assignment_id' => $assignment->id,
                        'question_id' => $questionId,
                        'choice_id' => $choiceId,
                        'answer_text' => null,
                    ]);
                }
            } elseif ($question->type === 'text') {
                Answer::create([
                    'assignment_id' => $assignment->id,
                    'question_id' => $questionId,
                    'choice_id' => null,
                    'answer_text' => $answer,
                ]);
            } else {
                Answer::create([
                    'assignment_id' => $assignment->id,
                    'question_id' => $questionId,
                    'choice_id' => $answer,
                    'answer_text' => null,
                ]);
            }
        }
    }

    public function getUserAnswers(ExamAssignment $assignment): Collection
    {
        return Answer::where('assignment_id', $assignment->id)
            ->with(['choice', 'question'])
            ->get()
            ->groupBy('question_id')
            ->map(function ($questionAnswers) {
                if ($questionAnswers->count() === 1) {
                    $answer = $questionAnswers->first();
                    return [
                        'type' => 'single',
                        'choice_id' => $answer->choice_id,
                        'answer_text' => $answer->answer_text,
                        'choice' => $answer->choice,
                    ];
                } else {
                    return [
                        'type' => 'multiple',
                        'choices' => $questionAnswers->map(function ($answer) {
                            return [
                                'choice_id' => $answer->choice_id,
                                'choice' => $answer->choice,
                            ];
                        })->toArray(),
                        'answer_text' => null,
                    ];
                }
            });
    }

    public function prepareAnswerData(Question $question, array $requestData): array
    {
        if (in_array($question->type, ['multiple', 'one_choice', 'boolean'])) {
            return [
                'choice_id' => $requestData['choice_id'],
                'answer_text' => null,
            ];
        }

        return [
            'answer_text' => $requestData['answer_text'] ?? '',
            'choice_id' => null,
        ];
    }
}
