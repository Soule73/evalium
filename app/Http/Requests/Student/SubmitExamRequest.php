<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for submitting an exam by a student.
 *
 * This request class is responsible for authorizing the student and validating
 * the input data when submitting an exam.
 */
class SubmitExamRequest extends FormRequest
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
            'answers' => ['array'],
            'security_violation' => ['boolean'],
            'violation_type' => ['string'],
        ];
    }
}
