<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ClassValidationRules;
use App\Models\ClassModel;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
{
    use ClassValidationRules, FiltersAcademicYear;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ClassModel::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = $this->getClassValidationRules();

        $academicYearId = $this->getSelectedAcademicYearId($this);
        $rules['name'][] = Rule::unique('classes')->where(function ($query) use ($academicYearId) {
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
}
