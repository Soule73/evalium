<?php

namespace App\Http\Requests\Admin;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Enrollment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'exists:users,id',
                Rule::unique('enrollments')->where(function ($query) {
                    return $query->where('class_id', $this->input('class_id'))
                        ->where('status', '!=', 'withdrawn');
                }),
            ],
            'class_id' => ['required', 'exists:classes,id'],
            'enrolled_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(EnrollmentStatus::values())],
            'send_credentials' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => __('validation.required', ['attribute' => __('messages.student')]),
            'student_id.exists' => __('validation.exists', ['attribute' => __('messages.student')]),
            'student_id.unique' => __('messages.student_already_enrolled'),
            'class_id.required' => __('validation.required', ['attribute' => __('messages.class')]),
            'class_id.exists' => __('validation.exists', ['attribute' => __('messages.class')]),
        ];
    }
}
