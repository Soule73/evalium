<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rule;

/**
 * Trait ClassValidationRules
 *
 * Provides shared validation rules and messages for Class form requests.
 * Eliminates duplication between StoreClassRequest and UpdateClassRequest.
 */
trait ClassValidationRules
{
    /**
     * Get the base validation rules for class entities.
     *
     * @param  int|null  $classId  The class ID to ignore for unique validation (for updates)
     * @return array<string, array<int, mixed>>
     */
    protected function getClassValidationRules(?int $classId = null): array
    {
        $uniqueRule = Rule::unique('classes')->where(function ($query) {
            return $query->where('academic_year_id', $this->input('academic_year_id'))
                ->where('level_id', $this->input('level_id'));
        });

        if ($classId !== null) {
            $uniqueRule->ignore($classId);
        }

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'description' => ['nullable', 'string'],
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
