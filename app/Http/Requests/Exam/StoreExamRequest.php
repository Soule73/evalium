<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Strategies\Validation\QuestionValidationContext;

/**
 * Handles validation logic for storing a new exam by a teacher.
 *
 * This request class is responsible for authorizing the user and validating
 * the incoming data when creating a new exam resource.
 * @package App\Http\Requests\Exam
 */
class StoreExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Exam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'duration' => 'required|integer|min:1|max:480',
            'start_time' => 'nullable|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'is_active' => 'boolean',

            'questions' => 'required|array|min:1|max:50',
            'questions.*.content' => 'required|string|max:1000',
            'questions.*.type' => 'required|in:text,multiple,one_choice,boolean',
            'questions.*.points' => 'required|integer|min:1|max:100',

            'questions.*.choices' => 'array|nullable',
            'questions.*.choices.*.content' => 'required_with:questions.*.choices|string|max:255',
            'questions.*.choices.*.is_correct' => 'nullable|boolean',
            'questions.*.choices.*.order_index' => 'required|integer|min:0',

        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules have been applied.
     *
     * This method allows you to add custom validation rules or modify the validator instance
     * before the request is considered valid.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator The validator instance to be configured.
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $questions = $data['questions'] ?? [];

            $validationContext = new QuestionValidationContext();
            $validationContext->validateQuestions($validator, $questions);
        });
    }
}
