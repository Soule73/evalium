<?php

namespace App\Http\Requests\Teacher;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for updating a teacher's score.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when a teacher attempts to update a score.
 *
 * @package App\Http\Requests\Teacher
 */
class UpdateScoreRequest extends FormRequest
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
     * @return array The array of validation rules for updating a teacher's score.
     */
    public function rules(): array
    {
        return [
            'exam_id' => [
                'required',
                'exists:exams,id'
            ],
            'student_id' => [
                'required',
                'exists:users,id'
            ],
            'question_id' => [
                'required',
                'exists:questions,id'
            ],
            'score' => [
                'required',
                'numeric',
                'min:0'
            ],
            'feedback' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'teacher_notes' => [
                'nullable',
                'string',
                'max:1000'
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
            'exam_id.required' => "L'ID de l'examen est requis.",
            'exam_id.exists' => "L'examen spécifié n'existe pas.",
            'student_id.required' => "L'ID de l'étudiant est requis.",
            'student_id.exists' => "L'étudiant spécifié n'existe pas.",
            'question_id.required' => "L'ID de la question est requis.",
            'question_id.exists' => "La question spécifiée n'existe pas.",
            'score.required' => "La note est requise.",
            'score.numeric' => "La note doit être un nombre.",
            'score.min' => "La note ne peut pas être négatif.",
            'feedback.string' => "Le commentaire doit être du texte.",
            'feedback.max' => "Le commentaire ne peut pas dépasser 1000 caractères."
        ];
    }

    /**
     * Configure additional validation logic after the initial validation rules have been applied.
     *
     * This method allows for custom validation to be performed using the provided validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance to use for custom validation.
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (isset($data['exam_id']) && isset($data['question_id'])) {
                $question = \App\Models\Question::where('id', $data['question_id'])
                    ->where('exam_id', $data['exam_id'])
                    ->first();

                if (!$question) {
                    $validator->errors()->add(
                        'question_id',
                        "Cette question n'appartient pas à l'examen spécifié."
                    );
                } else {
                    if (isset($data['score']) && $data['score'] > $question->points) {
                        $validator->errors()->add(
                            'score',
                            "Le score ne peut pas dépasser {$question->points} points pour cette question."
                        );
                    }
                }
            }

            if (isset($data['student_id']) && isset($data['question_id']) && isset($data['exam_id'])) {
                $assignment = \App\Models\ExamAssignment::where('student_id', $data['student_id'])
                    ->where('exam_id', $data['exam_id'])
                    ->first();

                if (!$assignment) {
                    $validator->errors()->add(
                        'student_id',
                        "Cet étudiant n'est pas assigné à cet examen."
                    );
                } else {
                    $answer = \App\Models\Answer::where('assignment_id', $assignment->id)
                        ->where('question_id', $data['question_id'])
                        ->first();

                    if (!$answer) {
                        $validator->errors()->add(
                            'student_id',
                            "Cet étudiant n'a pas répondu à cette question."
                        );
                    }
                }
            }

            if (isset($data['student_id'])) {
                $student = User::find($data['student_id']);
                if (!$student || !$student->hasRole('student')) {
                    $validator->errors()->add(
                        'student_id',
                        "L'utilisateur spécifié n'est pas un étudiant."
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation before the request is processed.
     *
     * This method can be used to modify or sanitize the input data,
     * such as merging additional fields or transforming values,
     * before the validation rules are applied.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (request()->route('exam') && !request()->has('exam_id')) {
            request()->merge(['exam_id' => request()->route('exam')]);
        }
    }
}
