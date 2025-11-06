<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation for security violation requests submitted by students.
 *
 * This request class is responsible for authorizing and validating
 * incoming data related to security violations in the student context.
 *
 * @package App\Http\Requests\Student
 */
class SecurityViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'violation_type' => ['required', 'string'],
            'violation_details' => ['nullable', 'string'],
            'answers' => ['array'],
        ];
    }
}
