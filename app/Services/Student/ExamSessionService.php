<?php

namespace App\Services\Student;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Core\Answer\AnswerFormatter;

class ExamSessionService
{
    public function __construct(
        private readonly ScoringService $scoringService,
        private readonly AnswerFormatter $answerFormatter
    ) {}

    public function findOrCreateAssignment(Exam $exam, User $student): ExamAssignment
    {
        return ExamAssignment::firstOrCreate([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function startExam(ExamAssignment $assignment): void
    {
        if ($assignment->started_at === null) {
            $assignment->update([
                'started_at' => Carbon::now(),
            ]);
        }
    }

    public function submitExam(ExamAssignment $assignment, ?float $autoScore, bool $hasTextQuestions = false, bool $isSecurityViolation = false): void
    {
        $submissionTime = Carbon::now();

        $finalStatus = ($hasTextQuestions || $isSecurityViolation) ? 'submitted' : 'submitted';

        $assignment->update([
            'status' => $finalStatus,
            'submitted_at' => $submissionTime,
            'forced_submission' => $isSecurityViolation,
            'score' => ($hasTextQuestions || $isSecurityViolation) ? null : $autoScore,
            'auto_score' => $autoScore ?? $assignment->auto_score,
        ]);
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

    /**
     * Récupérer toutes les réponses de l'étudiant pour une assignation
     * 
     * @deprecated Utiliser directement AnswerFormatter::formatForFrontend()
     */
    public function getUserAnswers(ExamAssignment $assignment): Collection
    {
        return collect($this->answerFormatter->formatForFrontend($assignment));
    }

    /**
     * Prépare les données de réponse selon le type de question
     * 
     * @deprecated Utiliser directement AnswerFormatter::prepareAnswerData()
     */
    public function prepareAnswerData(Question $question, array $requestData): array
    {
        return $this->answerFormatter->prepareAnswerData($question->type, $requestData);
    }
}
