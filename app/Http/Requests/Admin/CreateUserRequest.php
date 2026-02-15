<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\UserValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->getUserValidationRules();
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->getUserValidationMessages();
    }
}
