<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SaveStudentReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('teacher')) {
            return false;
        }

        $exam = request()->route()->parameter('exam');
        if ($exam) {
            return $exam->teacher_id === $user->id;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array The array of validation rules.
     */
    public function rules(): array
    {
        return [
            'scores' => [
                'required',
                'array',
                'min:1'
            ],
            'scores.*.question_id' => [
                'required',
                'integer',
                'exists:questions,id'
            ],
            'scores.*.score' => [
                'required',
                'numeric',
                'min:0'
            ],
            'scores.*.feedback' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'teacher_notes' => [
                'nullable',
                'string',
                'max:2000'
            ]
        ];
    }

    /**
     * Get the custom validation messages for the request.
     *
     * @return array An associative array of custom error messages.
     */
    public function messages(): array
    {
        return [
            'scores.required' => 'Les notes sont requis.',
            'scores.array' => 'Les notes doivent être fournis sous forme de tableau.',
            'scores.min' => 'Au moins une note doit être fournie.',
            'scores.*.question_id.required' => 'L\'ID de la question est requis.',
            'scores.*.question_id.integer' => 'L\'ID de la question doit être un entier.',
            'scores.*.question_id.exists' => 'La question spécifiée n\'existe pas.',
            'scores.*.score.required' => 'La note est requise.',
            'scores.*.score.numeric' => 'La note doit être un nombre.',
            'scores.*.score.min' => 'La note ne peut pas être négatif.',
            'scores.*.feedback.string' => 'Le commentaire doit être du texte.',
            'scores.*.feedback.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',
            'teacher_notes.string' => 'Les notes du professeur doivent être du texte.',
            'teacher_notes.max' => 'Les notes du professeur ne peuvent pas dépasser 2000 caractères.'
        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules.
     *
     * This method allows you to add custom validation logic or modify the validator instance
     * before the validation process is completed.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance.
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $exam = request()->route()->parameter('exam');

            if (!$exam || !isset($data['scores'])) {
                return;
            }

            foreach ($data['scores'] as $index => $scoreData) {
                if (!isset($scoreData['question_id']) || !isset($scoreData['score'])) {
                    continue;
                }

                $question = $exam->questions()->where('id', $scoreData['question_id'])->first();

                if (!$question) {
                    $validator->errors()->add(
                        "scores.{$index}.question_id",
                        "Cette question n'appartient pas à l'examen spécifié."
                    );
                } else {
                    if ($scoreData['score'] > $question->points) {
                        $validator->errors()->add(
                            "scores.{$index}.score",
                            "La note ne peut pas dépasser {$question->points} points pour cette question."
                        );
                    }
                }
            }
        });
    }
}
