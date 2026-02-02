<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Subject::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'level_id.required' => __('validation.required', ['attribute' => __('messages.level')]),
            'level_id.exists' => __('validation.exists', ['attribute' => __('messages.level')]),
            'name.required' => __('validation.required', ['attribute' => __('messages.subject_name')]),
            'code.required' => __('validation.required', ['attribute' => __('messages.subject_code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('messages.subject_code')]),
        ];
    }
}
