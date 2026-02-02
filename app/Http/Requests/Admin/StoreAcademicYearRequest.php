<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\AcademicYear::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:academic_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['sometimes', 'boolean'],
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
