<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for saving student reviews by a teacher.
 *
 * This request class is responsible for authorizing the user and validating
 * the input data when a teacher attempts to save reviews for students.
 * @package App\Http\Requests\Exam
 */
class SaveStudentReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $this->user()->can('review', $exam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
                        __('validation.custom.question_id.not_in_exam')
                    );
                } else {
                    if ($scoreData['score'] > $question->points) {
                        $validator->errors()->add(
                            "scores.{$index}.score",
                            __('validation.custom.score.exceeds_max', ['max' => $question->points])
                        );
                    }
                }
            }
        });
    }
}
