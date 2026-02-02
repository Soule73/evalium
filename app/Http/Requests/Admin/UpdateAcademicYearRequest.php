<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('academic_year'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $academicYearId = $this->route('academic_year')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'name')->ignore($academicYearId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('messages.academic_year_name')]),
            'name.unique' => __('validation.unique', ['attribute' => __('messages.academic_year_name')]),
            'start_date.required' => __('validation.required', ['attribute' => __('messages.start_date')]),
            'end_date.required' => __('validation.required', ['attribute' => __('messages.end_date')]),
            'end_date.after' => __('validation.after', ['attribute' => __('messages.end_date'), 'date' => __('messages.start_date')]),
        ];
    }
}
