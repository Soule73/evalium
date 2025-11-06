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

        $exam = $this->route('exam');

        return  $this->user()->can('view', $exam);
    }

    /**
     * Règles de validation pour la demande.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
                'in:submitted,graded'
            ],
            'search' => [
                'nullable',
                'string',
                'max:255'
            ]
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
