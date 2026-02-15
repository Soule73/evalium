<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

/**
 * Provides shared validation rules and messages for Class form requests.
 * Eliminates duplication between StoreClassRequest and UpdateClassRequest.
 *
 * Each consumer must append their own unique constraint to the 'name' rule,
 * scoped by academic_year_id and level_id.
 */
trait ClassValidationRules
{
    /**
     * Get the base validation rules for class entities.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function getClassValidationRules(): array
    {
        return [
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom validation messages for class entities.
     *
     * @return array<string, string>
     */
    protected function getClassValidationMessages(): array
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
