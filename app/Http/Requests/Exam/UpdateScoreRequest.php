<?php

namespace App\Http\Requests\Exam;

use App\Models\User;
use App\Strategies\Validation\Score\ScoreValidationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for updating a teacher's score.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when a teacher attempts to update a score.
 *
 * @package App\Http\Requests\Exam
 */
class UpdateScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {


        $exam = $this->route('exam');

        return $this->user()->can('review', $exam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => [
                'required',
                'exists:exams,id'
            ],
            'student_id' => [
                'required',
                'exists:users,id'
            ],
            'question_id' => [
                'required',
                'exists:questions,id'
            ],
            'score' => [
                'required',
                'numeric',
                'min:0'
            ],
            'feedback' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'teacher_notes' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules have been applied.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance to use for custom validation.
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            $validationContext = new ScoreValidationContext();
            $validationContext->validate(
                $validator,
                $data,
                ['single_question_exists', 'student_assignment']
            );
        });
    }

    /**
     * Prepare the data for validation before the request is processed.
     *
     * This method can be used to modify or sanitize the input data,
     * such as merging additional fields or transforming values,
     * before the validation rules are applied.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (request()->route('exam') && !request()->has('exam_id')) {
            request()->merge(['exam_id' => request()->route('exam')]);
        }
    }
}
