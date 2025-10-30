<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AssignStudentsToGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Au moins un étudiant doit être sélectionné.',
            'student_ids.array' => 'Les données des étudiants doivent être un tableau.',
            'student_ids.min' => 'Au moins un étudiant doit être sélectionné.',
            'student_ids.*.integer' => 'L\'identifiant de l\'étudiant doit être un nombre entier.',
            'student_ids.*.exists' => 'Un ou plusieurs étudiants sélectionnés n\'existent pas.',
        ];
    }
}
