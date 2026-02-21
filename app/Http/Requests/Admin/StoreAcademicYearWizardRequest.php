<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ValidatesAcademicYearSemesters;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearWizardRequest extends FormRequest
{
    use ValidatesAcademicYearSemesters;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\AcademicYear::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'name' => ['required', 'string', 'max:255', 'unique:academic_years,name'],
                'class_ids' => ['nullable', 'array'],
                'class_ids.*' => ['integer', 'exists:classes,id'],
            ],
            $this->semesterRules()
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->semesterMessages();
    }
}
