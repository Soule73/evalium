<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\Traits\AssessmentValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssessmentRequest extends FormRequest
{
    use AssessmentValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Assessment::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareAssessmentForValidation();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getAssessmentValidationRules(isUpdate: false);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $this->configureAssessmentValidator($validator);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getAssessmentValidationMessages(isUpdate: false);
    }
}
