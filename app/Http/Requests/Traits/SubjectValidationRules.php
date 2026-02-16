<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rule;

/**
 * Shared validation rules and messages for Subject form requests.
 *
 * Eliminates duplication between StoreSubjectRequest and UpdateSubjectRequest.
 */
trait SubjectValidationRules
{
    /**
     * Get the base validation rules for subject entities.
     *
     * @param  int|null  $subjectId  The subject ID to ignore for unique validation (for updates)
     * @return array<string, array<int, mixed>>
     */
    protected function getSubjectValidationRules(?int $subjectId = null): array
    {
        $uniqueCodeRule = Rule::unique('subjects', 'code');

        if ($subjectId !== null) {
            $uniqueCodeRule->ignore($subjectId);
        }

        return [
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', $uniqueCodeRule],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages for subject entities.
     *
     * @return array<string, string>
     */
    protected function getSubjectValidationMessages(): array
    {
        return [
            'level_id.required' => __('validation.required', ['attribute' => __('messages.level')]),
            'level_id.exists' => __('validation.exists', ['attribute' => __('messages.level')]),
            'name.required' => __('validation.required', ['attribute' => __('messages.subject_name')]),
            'code.required' => __('validation.required', ['attribute' => __('messages.subject_code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('messages.subject_code')]),
        ];
    }
}
