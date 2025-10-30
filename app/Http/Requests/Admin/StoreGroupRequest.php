<?php

namespace App\Http\Requests\Admin;

use App\Models\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'max_students' => ['required', 'integer', 'min:1', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_id.required' => 'Le niveau académique est obligatoire.',
            'level_id.exists' => 'Le niveau académique sélectionné n\'existe pas.',
            'start_date.required' => 'La date de début est obligatoire.',
            'start_date.after_or_equal' => 'La date de début ne peut pas être antérieure à aujourd\'hui.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
            'max_students.required' => 'Le nombre maximum d\'étudiants est obligatoire.',
            'max_students.min' => 'Le nombre maximum d\'étudiants doit être d\'au moins 1.',
            'max_students.max' => 'Le nombre maximum d\'étudiants ne peut pas dépasser 100.',
            'academic_year.required' => 'L\'année académique est obligatoire.',
        ];
    }
}
