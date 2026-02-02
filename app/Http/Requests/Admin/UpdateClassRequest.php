<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
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
        $classId = $this->route('class')->id;

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes')->where(function ($query) {
                    return $query->where('academic_year_id', $this->input('academic_year_id'))
                        ->where('level_id', $this->input('level_id'));
                })->ignore($classId),
            ],
            'description' => ['nullable', 'string'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'academic_year_id.required' => __('validation.required', ['attribute' => __('messages.academic_year')]),
            'level_id.required' => __('validation.required', ['attribute' => __('messages.level')]),
            'name.required' => __('validation.required', ['attribute' => __('messages.class_name')]),
            'name.unique' => __('validation.unique', ['attribute' => __('messages.class_name')]),
            'max_students.min' => __('validation.min.numeric', ['attribute' => __('messages.max_students'), 'min' => 1]),
        ];
    }
}
