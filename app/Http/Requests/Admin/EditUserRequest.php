<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class EditUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        $routeUser = request()->user();

        return $user && ($user->hasRole(['admin', 'super_admin']) || ($routeUser && $user === $routeUser));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . request()->user()->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:admin,teacher,student,super_admin'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => "L'adresse e-mail est requise.",
            'email.string' => "L'adresse e-mail doit être une chaîne de caractères.",
            'email.email' => "L'adresse e-mail doit être une adresse e-mail valide.",
            'email.max' => "L'adresse e-mail ne doit pas dépasser 255 caractères.",
            'email.unique' => "Cette adresse e-mail est déjà utilisée.",
            'password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est requis.',
            'role.in' => 'Le rôle sélectionné est invalide. Choisissez parmi : admin, super_admin, teacher, student.',
        ];
    }
}
