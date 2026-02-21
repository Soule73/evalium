<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ClassSubject::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'coefficient' => ['required', 'numeric', 'min:0.01'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_id.required' => __('validation.required', ['attribute' => __('messages.class')]),
            'subject_id.required' => __('validation.required', ['attribute' => __('messages.subject')]),
            'coefficient.required' => __('validation.required', ['attribute' => __('messages.coefficient')]),
            'coefficient.min' => __('validation.min.numeric', ['attribute' => __('messages.coefficient'), 'min' => 0.01]),
            'valid_to.after' => __('validation.after', ['attribute' => __('messages.valid_to'), 'date' => __('messages.valid_from')]),
        ];
    }
}
