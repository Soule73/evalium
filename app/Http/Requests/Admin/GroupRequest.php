<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() && Auth::user()->hasRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $groupId = request()->route()->parameter('group')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:groups,name' . ($groupId ? ",{$groupId}" : '')
            ],
            'description' => 'nullable|string|max:1000',
            'level_id' => 'required|integer|exists:levels,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'max_students' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'academic_year' => 'nullable|string|max:9|regex:/^\d{4}-\d{4}$/',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du groupe est obligatoire.',
            'name.unique' => 'Ce nom de groupe existe déjà.',
            'name.max' => 'Le nom du groupe ne peut pas dépasser 255 caractères.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'level_id.required' => 'Le niveau académique est obligatoire.',
            'level_id.integer' => 'Le niveau académique doit être un identifiant valide.',
            'level_id.exists' => 'Le niveau académique sélectionné n\'existe pas.',
            'start_date.required' => 'La date de début est obligatoire.',
            'start_date.date' => 'La date de début doit être une date valide.',
            'start_date.after_or_equal' => 'La date de début doit être aujourd\'hui ou dans le futur.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.date' => 'La date de fin doit être une date valide.',
            'end_date.after' => 'La date de fin doit être après la date de début.',
            'max_students.required' => 'Le nombre maximum d\'étudiants est obligatoire.',
            'max_students.integer' => 'Le nombre maximum d\'étudiants doit être un nombre entier.',
            'max_students.min' => 'Le nombre maximum d\'étudiants doit être au moins 1.',
            'max_students.max' => 'Le nombre maximum d\'étudiants ne peut pas dépasser 100.',
            'academic_year.regex' => 'L\'année académique doit être au format YYYY-YYYY (ex: 2024-2025).',
        ];
    }
}
