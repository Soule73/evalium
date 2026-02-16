<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\SubjectValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    use SubjectValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('subject'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getSubjectValidationRules($this->route('subject')->id);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getSubjectValidationMessages();
    }
}
