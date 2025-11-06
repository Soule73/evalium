<?php

namespace App\Http\Requests\Exam;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Strategies\Validation\QuestionValidationContext;

/**
 * Handles validation logic for updating an exam by a teacher.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when a teacher attempts to update an existing exam.
 *
 * @package App\Http\Requests\Exam
 */
class UpdateExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $this->user()->can('update', $exam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration' => 'required|integer|min:1|max:480',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'is_active' => 'boolean',

            'questions' => 'required|array|min:1|max:50',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.content' => 'required|string|max:1000',
            'questions.*.type' => 'required|in:text,multiple,one_choice,boolean',
            'questions.*.points' => 'required|integer|min:1|max:100',
            'questions.*.order_index' => 'required|integer|min:0',

            'questions.*.choices' => 'array|nullable',
            'questions.*.choices.*.id' => 'nullable|integer|exists:choices,id',
            'questions.*.choices.*.content' => 'required_with:questions.*.choices|string|max:255',
            'questions.*.choices.*.is_correct' => 'nullable|boolean',
            'questions.*.choices.*.order_index' => 'required|integer|min:0',

            'questions.*.suggested_answer' => 'nullable|string|max:1000',

            'deletedQuestionIds' => 'nullable|array',
            'deletedQuestionIds.*' => 'integer|exists:questions,id',
            'deletedChoiceIds' => 'nullable|array',
            'deletedChoiceIds.*' => 'integer|exists:choices,id',
        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules have been applied.
     *
     * This method allows you to add custom validation rules or modify the validator instance
     * before the request is considered valid. It is called automatically by Laravel during
     * the request validation process.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance to be configured.
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $questions = $data['questions'] ?? [];

            // Use the Strategy Pattern to validate questions
            $validationContext = new QuestionValidationContext();
            $validationContext->validateQuestions($validator, $questions);
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $questions = request()->input('questions', []);

        \Illuminate\Support\Facades\Log::warning('Validation échouée pour UpdateExamRequest', [
            'errors' => $validator->errors()->toArray(),
            'input_questions_count' => count($questions),
            'questions_summary' => collect($questions)->map(function ($q, $index) {
                return [
                    'index' => $index,
                    'type' => $q['type'] ?? 'unknown',
                    'choices_count' => is_array($q['choices'] ?? null) ? count($q['choices']) : 'null/invalid',
                    'has_content' => !empty($q['content'] ?? ''),
                ];
            })->toArray()
        ]);

        parent::failedValidation($validator);
    }
}
