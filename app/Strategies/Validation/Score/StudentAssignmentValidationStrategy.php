<?php

namespace App\Strategies\Validation\Score;

use App\Models\Answer;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Validation\Validator;

class StudentAssignmentValidationStrategy implements ScoreValidationStrategy
{
    public function validate(Validator $validator, array $data, array $context = []): void
    {
        if (! isset($data['student_id']) || ! isset($data['exam_id'])) {
            return;
        }

        $student = User::find($data['student_id']);
        if (! $student || ! $student->hasRole('student')) {
            $validator->errors()->add(
                'student_id',
                __('validation.custom.student_id.not_student')
            );

            return;
        }

        $assignment = ExamAssignment::where('student_id', $data['student_id'])
            ->where('exam_id', $data['exam_id'])
            ->first();

        if (! $assignment) {
            $validator->errors()->add(
                'student_id',
                __('validation.custom.student_id.not_assigned')
            );

            return;
        }

        if (isset($data['question_id'])) {
            $answer = Answer::where('assignment_id', $assignment->id)
                ->where('question_id', $data['question_id'])
                ->first();

            if (! $answer) {
                $validator->errors()->add(
                    'student_id',
                    __('validation.custom.student_id.no_answer')
                );
            }
        }
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'student_assignment';
    }
}
