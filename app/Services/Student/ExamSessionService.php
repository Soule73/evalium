<?php

namespace App\Services\Student;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\ExamAssignment;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Core\Answer\AnswerFormatterService;

/**
 * Service for managing exam session lifecycle.
 * 
 * Handles exam assignment creation, starting, submitting, and answer management
 * for student exam sessions.
 */
class ExamSessionService
{
    public function __construct(
        private readonly ScoringService $scoringService,
        private readonly AnswerFormatterService $answerFormatter
    ) {}

    /**
     * Find existing or create new exam assignment for a student.
     *
     * @param Exam $exam The exam to assign
     * @param User $student The student taking the exam
     * @return ExamAssignment The found or created assignment
     */
    public function findOrCreateAssignment(Exam $exam, User $student): ExamAssignment
    {
        return ExamAssignment::firstOrCreate([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    /**
     * Start an exam session by recording the start time.
     * 
     * Only sets start time if not already started (prevents override).
     *
     * @param ExamAssignment $assignment The assignment to start
     * @return void
     */
    public function startExam(ExamAssignment $assignment): void
    {
        if ($assignment->started_at === null) {
            $assignment->update([
                'started_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Submit an exam and finalize the assignment.
     * 
     * Handles different submission scenarios:
     * - Normal submission with auto-scoring
     * - Submission with text questions (requires manual grading)
     * - Forced submission due to security violations
     *
     * @param ExamAssignment $assignment The assignment to submit
     * @param float|null $autoScore Automatically calculated score
     * @param bool $hasTextQuestions Whether exam contains text questions
     * @param bool $isSecurityViolation Whether submission is due to violation
     * @param string|null $violationType Type of security violation if applicable
     * @return void
     */
    public function submitExam(
        ExamAssignment $assignment,
        ?float $autoScore,
        bool $hasTextQuestions = false,
        bool $isSecurityViolation = false,
        ?string $violationType = null
    ): void {
        $submissionTime = Carbon::now();

        $finalStatus = ($hasTextQuestions || $isSecurityViolation) ? 'submitted' : 'submitted';

        $updateData = [
            'status' => $finalStatus,
            'submitted_at' => $submissionTime,
            'forced_submission' => $isSecurityViolation,
            'score' => ($hasTextQuestions || $isSecurityViolation) ? null : $autoScore,
            'auto_score' => $autoScore ?? $assignment->auto_score,
        ];

        if ($isSecurityViolation && $violationType) {
            $updateData['security_violation'] = $violationType;
        }

        $assignment->update($updateData);
    }

    /**
     * Clear all answers for a specific question.
     * 
     * Used before saving new answers to prevent duplicates.
     *
     * @param ExamAssignment $assignment The assignment
     * @param int $questionId The question ID to clear answers for
     * @return void
     */
    private function clearAnswersForQuestion(ExamAssignment $assignment, int $questionId): void
    {
        Answer::where('assignment_id', $assignment->id)
            ->where('question_id', $questionId)
            ->delete();
    }

    /**
     * Save a single answer for a question.
     * 
     * Delegates to type-specific methods based on question type.
     * Enforces strict type validation:
     * - Multiple choice: expects array of choice IDs
     * - Text/essay: expects string
     * - Single choice/boolean: expects single choice ID
     *
     * @param ExamAssignment $assignment The assignment
     * @param \App\Models\Question $question The question being answered
     * @param mixed $answer The answer data (array for multiple choice, string/int otherwise)
     * @return void
     */
    private function saveSingleAnswer(ExamAssignment $assignment, $question, $answer): void
    {
        $this->clearAnswersForQuestion($assignment, $question->id);

        match ($question->type) {
            'multiple' => $this->saveMultipleChoiceAnswer($assignment, $question->id, $answer),
            'text', 'essay' => $this->saveTextAnswer($assignment, $question->id, $answer),
            default => $this->saveChoiceAnswer($assignment, $question->id, $answer),
        };
    }

    /**
     * Save multiple choice answer (multiple selections).
     * 
     * Expects an array of choice IDs. If a single ID is provided,
     * it will be wrapped in an array for consistency.
     *
     * @param ExamAssignment $assignment The assignment
     * @param int $questionId The question ID
     * @param mixed $choiceIds Array of selected choice IDs
     * @return void
     */
    private function saveMultipleChoiceAnswer(ExamAssignment $assignment, int $questionId, $choiceIds): void
    {
        if (!is_array($choiceIds)) {
            $choiceIds = [$choiceIds];
        }

        if (empty($choiceIds)) {
            return;
        }

        foreach ($choiceIds as $choiceId) {
            Answer::create([
                'assignment_id' => $assignment->id,
                'question_id' => $questionId,
                'choice_id' => $choiceId,
                'answer_text' => null,
            ]);
        }
    }

    /**
     * Save text answer (essay, short answer).
     *
     * @param ExamAssignment $assignment The assignment
     * @param int $questionId The question ID
     * @param mixed $text The answer text
     * @return void
     */
    private function saveTextAnswer(ExamAssignment $assignment, int $questionId, $text): void
    {
        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $questionId,
            'choice_id' => null,
            'answer_text' => $text,
        ]);
    }

    /**
     * Save single choice answer (one_choice, boolean).
     *
     * @param ExamAssignment $assignment The assignment
     * @param int $questionId The question ID
     * @param mixed $choiceId The selected choice ID
     * @return void
     */
    private function saveChoiceAnswer(ExamAssignment $assignment, int $questionId, $choiceId): void
    {
        Answer::create([
            'assignment_id' => $assignment->id,
            'question_id' => $questionId,
            'choice_id' => $choiceId,
            'answer_text' => null,
        ]);
    }

    /**
     * Save multiple answers in batch.
     * 
     * Processes answers for multiple questions, delegating to type-specific methods.
     *
     * @param ExamAssignment $assignment The assignment to save answers for
     * @param Exam $exam The exam being taken
     * @param array<int, mixed> $answers Array of answers keyed by question ID
     * @return void
     */
    public function saveMultipleAnswers(ExamAssignment $assignment, Exam $exam, array $answers): void
    {
        foreach ($answers as $questionId => $answer) {
            $question = $exam->questions()->find($questionId);

            if (!$question) {
                continue;
            }

            $this->saveSingleAnswer($assignment, $question, $answer);
        }
    }

    /**
     * Validate if exam is accessible based on timing constraints.
     * 
     * Returns true if:
     * - Exam has no time constraints, OR
     * - Current time is between start_time and end_time
     *
     * @param Exam $exam The exam to validate
     * @param Carbon|null $now Reference datetime (null = now)
     * @return bool True if exam is currently accessible
     */
    public function validateExamTiming(Exam $exam, ?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();

        if (!$exam->start_time || !$exam->end_time) {
            return true;
        }

        return $now->between($exam->start_time, $exam->end_time);
    }
}
