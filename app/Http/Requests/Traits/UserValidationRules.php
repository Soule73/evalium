<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rule;

/**
 * Shared validation rules and messages for User form requests.
 *
 * Eliminates duplication between CreateUserRequest and EditUserRequest.
 */
trait UserValidationRules
{
    /**
     * Get the base validation rules for user entities.
     *
     * @param  int|null  $userId  The user ID to ignore for unique validation (for updates)
     * @return array<string, array<int, mixed>>
     */
    protected function getUserValidationRules(?int $userId = null): array
    {
        $uniqueEmailRule = Rule::unique('users', 'email');

        if ($userId !== null) {
            $uniqueEmailRule->ignore($userId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $uniqueEmailRule],
            'role' => ['required', 'string', 'in:admin,teacher,student,super_admin'],
        ];
    }

    /**
     * Get custom validation messages for user entities.
     *
     * @return array<string, string>
     */
    protected function getUserValidationMessages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('messages.name')]),
            'email.required' => __('validation.required', ['attribute' => __('messages.email')]),
            'email.email' => __('validation.email', ['attribute' => __('messages.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('messages.email')]),
            'role.required' => __('validation.required', ['attribute' => __('messages.role')]),
            'role.in' => __('validation.in', ['attribute' => __('messages.role')]),
        ];
    }
}
