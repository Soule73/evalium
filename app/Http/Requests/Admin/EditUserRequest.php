<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class EditUserRequest extends FormRequest
{
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = $this->getUserValidationRules($this->route('user')->id);
        $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge($this->getUserValidationMessages(), [
            'password.min' => __('validation.min.string', ['attribute' => __('messages.password'), 'min' => 8]),
        ]);
    }
}
