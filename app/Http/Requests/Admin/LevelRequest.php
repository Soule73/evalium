<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $levelId = request()->route('level')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('levels', 'name')->ignore($levelId),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('levels', 'code')->ignore($levelId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
