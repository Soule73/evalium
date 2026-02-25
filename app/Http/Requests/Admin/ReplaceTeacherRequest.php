<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_teacher_id' => ['required', 'exists:users,id', 'different:old_teacher_id'],
            'effective_date' => ['required', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_teacher_id.required' => __('validation.required', ['attribute' => __('messages.new_teacher')]),
            'new_teacher_id.exists' => __('validation.exists', ['attribute' => __('messages.new_teacher')]),
            'new_teacher_id.different' => __('messages.new_teacher_must_be_different'),
            'effective_date.required' => __('validation.required', ['attribute' => __('messages.effective_date')]),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'old_teacher_id' => $this->route('class_subject')->teacher_id,
        ]);
    }
}
