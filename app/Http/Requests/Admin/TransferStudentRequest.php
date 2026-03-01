<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('transfer', $this->route('enrollment')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_class_id' => ['required', 'exists:classes,id', 'different:old_class_id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_class_id.required' => __('validation.required', ['attribute' => __('messages.new_class')]),
            'new_class_id.exists' => __('validation.exists', ['attribute' => __('messages.new_class')]),
            'new_class_id.different' => __('messages.new_class_must_be_different'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'old_class_id' => $this->route('enrollment')->class_id,
        ]);
    }
}
