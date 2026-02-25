<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\ValidatesAcademicYearSemesters;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
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
        return array_merge(
            ['name' => ['required', 'string', 'max:255', 'unique:academic_years,name']],
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
