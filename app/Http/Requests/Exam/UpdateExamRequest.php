<?php

namespace App\Http\Requests\Exam;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for updating an exam by a teacher.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when a teacher attempts to update an existing exam.
 *
 * @package App\Http\Requests\Exam
 */
class UpdateExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user is authorized, false otherwise.
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration' => 'required|integer|min:1|max:480',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'is_active' => 'boolean',

            'questions' => 'required|array|min:1|max:50',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.content' => 'required|string|max:1000',
            'questions.*.type' => 'required|in:text,multiple,one_choice,boolean',
            'questions.*.points' => 'required|integer|min:1|max:100',
            'questions.*.order_index' => 'required|integer|min:0',

            'questions.*.choices' => 'array|nullable',
            'questions.*.choices.*.id' => 'nullable|integer|exists:choices,id',
            'questions.*.choices.*.content' => 'required_with:questions.*.choices|string|max:255',
            'questions.*.choices.*.is_correct' => 'nullable|boolean',
            'questions.*.choices.*.order_index' => 'required|integer|min:0',

            'questions.*.suggested_answer' => 'nullable|string|max:1000',

            'deletedQuestionIds' => 'nullable|array',
            'deletedQuestionIds.*' => 'integer|exists:questions,id',
            'deletedChoiceIds' => 'nullable|array',
            'deletedChoiceIds.*' => 'integer|exists:choices,id',
        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules have been applied.
     *
     * This method allows you to add custom validation rules or modify the validator instance
     * before the request is considered valid. It is called automatically by Laravel during
     * the request validation process.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance to be configured.
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $questions = $data['questions'] ?? [];

            foreach ($questions as $index => $question) {
                $questionType = $question['type'] ?? '';

                if (in_array($questionType, ['multiple', 'one_choice', 'boolean'])) {

                    if (!isset($question['choices']) || !is_array($question['choices']) || count($question['choices']) < 2) {
                        $validator->errors()->add(
                            "questions.{$index}.choices",
                            'Au moins 2 choix sont requis pour ce type de question.'
                        );
                        continue;
                    }

                    if ($questionType === 'multiple') {
                        $correctCount = 0;
                        foreach ($question['choices'] as $choice) {
                            if (isset($choice['is_correct']) && $choice['is_correct']) {
                                $correctCount++;
                            }
                        }

                        if ($correctCount < 2) {
                            $validator->errors()->add(
                                "questions.{$index}.choices",
                                'Au moins 2 réponses correctes doivent être sélectionnées pour une question à choix multiples.'
                            );
                        }
                    } elseif ($questionType === 'one_choice' || $questionType === 'boolean') {
                        $correctCount = 0;
                        foreach ($question['choices'] as $choice) {
                            if (isset($choice['is_correct']) && $choice['is_correct']) {
                                $correctCount++;
                            }
                        }

                        if ($correctCount !== 1) {
                            $questionTypeLabel = $questionType === 'one_choice' ? 'choix unique' : 'vrai/faux';
                            $validator->errors()->add(
                                "questions.{$index}.choices",
                                "Exactement 1 réponse correcte doit être sélectionnée pour une question à {$questionTypeLabel}."
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $questions = request()->input('questions', []);

        \Illuminate\Support\Facades\Log::warning('Validation échouée pour UpdateExamRequest', [
            'errors' => $validator->errors()->toArray(),
            'input_questions_count' => count($questions),
            'questions_summary' => collect($questions)->map(function ($q, $index) {
                return [
                    'index' => $index,
                    'type' => $q['type'] ?? 'unknown',
                    'choices_count' => is_array($q['choices'] ?? null) ? count($q['choices']) : 'null/invalid',
                    'has_content' => !empty($q['content'] ?? ''),
                ];
            })->toArray()
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the custom validation messages for the request.
     *
     * @return array An associative array of custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => "Le titre de l'examen est obligatoire.",
            'duration.required' => "La durée de l'examen est obligatoire.",
            'end_time.after' => "La date de fin doit être postérieure à la date de début.",
            'questions.required' => "Au moins une question est requise.",
            'questions.*.content.required' => "Le contenu de la question est obligatoire.",
            'questions.*.type.required' => "Le type de question est obligatoire.",
            'questions.*.type.in' => "Le type de question doit être : texte, choix multiples, choix unique ou vrai/faux.",
            'questions.*.points.required' => "Le nombre de points est obligatoire.",
            'questions.*.choices.required_if' => "Les choix sont obligatoires pour les questions à choix multiples et choix unique.",
            'questions.*.choices.*.content.required_if' => "Le contenu de chaque choix est obligatoire.",
            'questions.*.correct_answer.required_if' => "Vous devez indiquer si la réponse est vraie ou fausse.",
        ];
    }
}
