<?php

namespace App\Http\Requests\Teacher;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for assigning exams to teachers.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when assigning an exam to a teacher.
 *
 * @package App\Http\Requests\Teacher
 */
class AssignExamRequest extends FormRequest
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

        $route = request()->route();
        if (!$route) {
            return true;
        }

        $exam = $route->parameter('exam');
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
            'student_ids' => [
                'required',
                'array',
                'min:1',
                'max:100'
            ],
            'student_ids.*' => [
                'required',
                'integer',
                'exists:users,id',
                'distinct'
            ],
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
            'student_ids.required' => 'Vous devez sélectionner au moins un étudiant.',
            'student_ids.array' => 'Le format des étudiants sélectionnés est invalide.',
            'student_ids.min' => 'Vous devez sélectionner au moins un étudiant.',
            'student_ids.max' => 'Vous ne pouvez pas assigner plus de 100 étudiants à la fois.',
            'student_ids.*.exists' => 'Un ou plusieurs étudiants sélectionnés n\'existent pas.',
            'student_ids.*.distinct' => 'Vous avez sélectionné le même étudiant plusieurs fois.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string> An associative array mapping attribute names to their displayable names.
     */
    public function attributes(): array
    {
        return [
            'student_ids' => 'étudiants',
            'student_ids.*' => 'étudiant',
        ];
    }

    /**
     * Configure additional validation logic after the initial validation.
     *
     * This method allows you to add custom validation rules or modify the validator instance
     * before the validation process is completed.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance.
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $studentIds = $validator->getData()['student_ids'] ?? [];
            if (!empty($studentIds)) {
                $invalidStudents = collect($studentIds)->filter(function ($studentId) {
                    $user = User::find($studentId);
                    return !$user || !$user->hasRole('student');
                });

                if ($invalidStudents->isNotEmpty()) {
                    $validator->errors()->add(
                        'student_ids',
                        'Un ou plusieurs utilisateurs sélectionnés ne sont pas des étudiants.'
                    );
                }
            }
        });
    }
}
