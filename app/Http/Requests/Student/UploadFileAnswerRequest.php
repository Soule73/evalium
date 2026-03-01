<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates file upload requests for QuestionType::File answers.
 *
 * Constraints (max size, allowed extensions) are read from config/assessment.php
 * and apply system-wide to all file-type questions.
 */
class UploadFileAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $maxSizeKb = (int) config('assessment.file_uploads.max_size_kb', 10240);
        $allowedExtensions = implode(',', config('assessment.file_uploads.allowed_extensions', []));

        $rules = [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'file' => ['required', 'file', "max:{$maxSizeKb}"],
        ];

        if ($allowedExtensions) {
            $rules['file'][] = "mimes:{$allowedExtensions}";
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question_id.required' => __('validation.required', ['attribute' => __('messages.question')]),
            'question_id.exists' => __('validation.exists', ['attribute' => __('messages.question')]),
            'file.required' => __('validation.required', ['attribute' => __('messages.file')]),
            'file.file' => __('validation.file', ['attribute' => __('messages.file')]),
            'file.max' => __('messages.file_too_large'),
            'file.mimes' => __('messages.file_extension_not_allowed'),
        ];
    }
}
