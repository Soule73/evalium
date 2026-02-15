<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ClassValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    use ClassValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('class'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $class = $this->route('class');
        $rules = $this->getClassValidationRules();

        $rules['name'][] = Rule::unique('classes')->where(function ($query) use ($class) {
            return $query->where('academic_year_id', $class->academic_year_id)
                ->where('level_id', $this->input('level_id'));
        })->ignore($class->id);

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getClassValidationMessages();
    }
}
