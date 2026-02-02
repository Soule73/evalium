<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'class_subject_id' => ['required', 'exists:class_subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:devoir,examen,tp,controle,projet'],
            'scheduled_date' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:1'],
            'coefficient' => ['required', 'numeric', 'min:0.01'],
            'is_published' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:multiple_choice,single_choice,text'],
            'questions.*.points' => ['required', 'numeric', 'min:0'],
            'questions.*.choices' => ['required_if:questions.*.type,multiple_choice,single_choice', 'array'],
            'questions.*.choices.*.text' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ];
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
