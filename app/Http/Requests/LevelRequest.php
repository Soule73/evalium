<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        // Pour la création (pas de paramètre level dans la route)
        if (!request()->route('level')) {
            return $user->can('create levels');
        }

        // Pour la modification (paramètre level présent dans la route)
        return $user->can('update levels');
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
            'description' => 'nullable|string|max:1000',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'code' => 'code',
            'description' => 'description',
            'order' => 'ordre',
            'is_active' => 'statut',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du niveau est obligatoire.',
            'name.unique' => 'Ce nom de niveau existe déjà.',
            'code.required' => 'Le code du niveau est obligatoire.',
            'code.unique' => 'Ce code de niveau existe déjà.',
            'order.required' => "L'ordre est obligatoire.",
            'order.integer' => "L'ordre doit être un nombre entier.",
            'order.min' => "L'ordre doit être supérieur ou égal à 0.",
        ];
    }
}
