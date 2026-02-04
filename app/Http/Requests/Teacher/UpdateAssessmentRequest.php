<?php

namespace App\Http\Requests\Teacher;

use App\Strategies\Validation\QuestionValidationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('assessment'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('scheduled_date')) {
            $data['scheduled_at'] = $this->scheduled_date;
        }

        if ($this->has('duration')) {
            $data['duration_minutes'] = $this->duration;
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:devoir,examen,tp,controle,projet'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.01'],
            'is_published' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.type' => ['required', 'string'],
            'questions.*.points' => ['required', 'numeric', 'min:0'],
            'questions.*.order_index' => ['required', 'integer'],
            'questions.*.choices' => ['sometimes', 'array'],
            'questions.*.choices.*.id' => ['nullable', 'integer'],
            'questions.*.choices.*.content' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.order_index' => ['required', 'integer'],
            'deletedQuestionIds' => ['sometimes', 'array'],
            'deletedQuestionIds.*' => ['integer'],
            'deletedChoiceIds' => ['sometimes', 'array'],
            'deletedChoiceIds.*' => ['integer'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->has('questions') && is_array($this->questions)) {
                $validationContext = new QuestionValidationContext;
                $validationContext->validateQuestions($validator, $this->questions);
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.string' => __('validation.string', ['attribute' => __('messages.assessment_title')]),
            'type.in' => __('validation.in', ['attribute' => __('messages.assessment_type')]),
            'duration.min' => __('validation.min.numeric', ['attribute' => __('messages.duration'), 'min' => 1]),
            'coefficient.min' => __('validation.min.numeric', ['attribute' => __('messages.coefficient'), 'min' => 0.01]),
        ];
    }
}
