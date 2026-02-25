<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ValidatesAcademicYearSemesters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    use ValidatesAcademicYearSemesters;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $academicYearId = $this->route('academic_year')->id;

        return array_merge(
            [
                'name' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'name')->ignore($academicYearId)],
                'semesters.*.id' => ['nullable', 'integer', 'exists:semesters,id'],
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
