<?php

namespace Tests\Traits;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use App\Models\Group;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

trait CreatesTestAssignments
{
    protected function createAssignmentForStudent(Exam $exam, User $student, array $attributes = []): ExamAssignment
    {
        return ExamAssignment::factory()->create(array_merge([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ], $attributes));
    }

    protected function createStartedAssignment(Exam $exam, User $student, array $attributes = []): ExamAssignment
    {
        return ExamAssignment::factory()->create(array_merge([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ], $attributes));
    }

    protected function createSubmittedAssignment(Exam $exam, User $student, array $attributes = []): ExamAssignment
    {
        return ExamAssignment::factory()->create(array_merge([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ], $attributes));
    }

    protected function createGradedAssignment(Exam $exam, User $student, float $score = 80.0, array $attributes = []): ExamAssignment
    {
        return ExamAssignment::factory()->create(array_merge([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'status' => 'graded',
            'score' => $score,
        ], $attributes));
    }

    protected function assignExamToGroup(Exam $exam, Group $group, User $assignedBy = null): void
    {
        $assignedBy = $assignedBy ?? $exam->teacher;

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);
    }

    protected function createAnswerForQuestion(ExamAssignment $assignment, Question $question, array $attributes = [])
    {
        $defaultAttributes = [
            'exam_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ];

        if ($question->type === 'text') {
            $defaultAttributes['answer_text'] = 'Sample answer text';
        }

        return Answer::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    protected function createAnswersForAllQuestions(ExamAssignment $assignment, Exam $exam): array
    {
        $answers = [];

        foreach ($exam->questions as $question) {
            $answers[] = $this->createAnswerForQuestion($assignment, $question);
        }

        return $answers;
    }

    protected function createMultipleAssignments(Exam $exam, array $students, string $status = null): array
    {
        $assignments = [];

        foreach ($students as $student) {
            $attributes = [];

            if ($status === 'started') {
                $attributes['started_at'] = now();
            } elseif ($status === 'submitted') {
                $attributes['started_at'] = now()->subHour();
                $attributes['submitted_at'] = now();
                $attributes['status'] = 'submitted';
            } elseif ($status === 'graded') {
                $attributes['started_at'] = now()->subHours(2);
                $attributes['submitted_at'] = now()->subHour();
                $attributes['status'] = 'graded';
                $attributes['score'] = 75.0;
            }

            $assignments[] = $this->createAssignmentForStudent($exam, $student, $attributes);
        }

        return $assignments;
    }
}
