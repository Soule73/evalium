<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:devoir,examen,tp,controle,projet'],
            'scheduled_date' => ['sometimes', 'date'],
            'duration' => ['sometimes', 'integer', 'min:1'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.01'],
            'is_published' => ['sometimes', 'boolean'],
        ];
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
