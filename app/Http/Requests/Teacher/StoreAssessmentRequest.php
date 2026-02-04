<?php

namespace App\Http\Requests\Teacher;

use App\Strategies\Validation\QuestionValidationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Assessment::class);
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

        if (! $this->has('coefficient') || $this->coefficient === null) {
            $data['coefficient'] = 1.0;
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
            'class_subject_id' => ['required', 'exists:class_subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:devoir,examen,tp,controle,projet'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'coefficient' => ['required', 'numeric', 'min:0.01'],
            'is_published' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.type' => ['required', 'string'],
            'questions.*.points' => ['required', 'numeric', 'min:0'],
            'questions.*.order_index' => ['required', 'integer'],
            'questions.*.choices' => ['sometimes', 'array'],
            'questions.*.choices.*.content' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.order_index' => ['required', 'integer'],
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
            'class_subject_id.required' => __('validation.required', ['attribute' => __('messages.class_subject')]),
            'title.required' => __('validation.required', ['attribute' => __('messages.assessment_title')]),
            'type.required' => __('validation.required', ['attribute' => __('messages.assessment_type')]),
            'type.in' => __('validation.in', ['attribute' => __('messages.assessment_type')]),
            'scheduled_date.required' => __('validation.required', ['attribute' => __('messages.scheduled_date')]),
            'duration.required' => __('validation.required', ['attribute' => __('messages.duration')]),
            'duration.min' => __('validation.min.numeric', ['attribute' => __('messages.duration'), 'min' => 1]),
            'coefficient.required' => __('validation.required', ['attribute' => __('messages.coefficient')]),
            'coefficient.min' => __('validation.min.numeric', ['attribute' => __('messages.coefficient'), 'min' => 0.01]),
        ];
    }
}
