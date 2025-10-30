<?php

namespace App\Http\Requests\Exam;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation and authorization for requests to retrieve exam results by a teacher.
 *
 * This request class is typically used to ensure that the incoming request
 * contains valid data and that the user has the necessary permissions to access exam results.
 *
 * @package App\Http\Requests\Exam
 */
class GetExamResultsRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette demande.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('teacher')) {
            return false;
        }

        $examParam = request()->route()->parameter('exam');

        if ($examParam) {
            if (is_object($examParam)) {
                return $examParam->teacher_id === $user->id;
            }
            $exam = \App\Models\Exam::find($examParam);
            return $exam && $exam->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Règles de validation pour la demande.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'sort_by' => [
                'nullable',
                'string',
                'in:user_name,total_score,completed_at,status'
            ],
            'sort_direction' => [
                'nullable',
                'string',
                'in:asc,desc'
            ],
            'filter_status' => [
                'nullable',
                'string',
                'in:completed,in_progress,not_started'
            ],
            'search' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'per_page.integer' => 'Le nombre d\'éléments par page doit être un entier.',
            'per_page.min' => 'Le nombre d\'éléments par page doit être au moins 1.',
            'per_page.max' => 'Le nombre d\'éléments par page ne peut pas dépasser 100.',
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être au moins 1.',
            'sort_by.in' => 'Le tri doit être l\'un des suivants : user_name, total_score, completed_at, status.',
            'sort_direction.in' => 'La direction du tri doit être asc ou desc.',
            'filter_status.in' => 'Le filtre de statut doit être l\'un des suivants : completed, in_progress, not_started.',
            'search.string' => 'La recherche doit être du texte.',
            'search.max' => 'La recherche ne peut pas dépasser 255 caractères.'
        ];
    }

    /**
     * Valeurs par défaut après validation.
     */
    public function validatedWithDefaults(): array
    {
        $validated = parent::validated();

        return array_merge([
            'per_page' => 20,
            'page' => 1,
            'sort_by' => 'user_name',
            'sort_direction' => 'asc',
            'filter_status' => null,
            'search' => null
        ], $validated);
    }
}
