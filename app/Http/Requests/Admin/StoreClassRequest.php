<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ClassValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules = $this->getClassValidationRules();

        unset($rules['academic_year_id']);

        $academicYearId = $this->getAcademicYearIdFromSession();
        $rules['name'][3] = Rule::unique('classes')->where(function ($query) use ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId)
                ->where('level_id', $this->input('level_id'));
        });

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getClassValidationMessages();
    }

    /**
     * Get academic year ID from session or current year.
     */
    private function getAcademicYearIdFromSession(): ?int
    {
        return $this->session()->get('academic_year_id')
            ?? \App\Models\AcademicYear::where('is_current', true)->value('id');
    }
}
