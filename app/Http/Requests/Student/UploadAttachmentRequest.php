<?php

namespace App\Http\Requests\Student;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates file upload requests for homework assessment attachments.
 */
class UploadAttachmentRequest extends FormRequest
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
        /** @var Assessment $assessment */
        $assessment = $this->route('assessment');

        $maxSizeKb = $assessment->max_file_size ?? 10240;
        $allowedExtensions = $assessment->allowed_extensions;

        $rules = [
            'file' => ['required', 'file', "max:{$maxSizeKb}"],
        ];

        if ($allowedExtensions) {
            $extensions = implode(',', $assessment->getAllowedExtensionsArray());
            $rules['file'][] = "mimes:{$extensions}";
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
            'file.required' => __('validation.required', ['attribute' => __('messages.file')]),
            'file.file' => __('validation.file', ['attribute' => __('messages.file')]),
            'file.max' => __('messages.file_too_large'),
            'file.mimes' => __('messages.file_extension_not_allowed'),
        ];
    }
}
