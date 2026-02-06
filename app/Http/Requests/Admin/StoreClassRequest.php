<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ClassValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    use ClassValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ClassModel::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getClassValidationRules();
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getClassValidationMessages();
    }
}
