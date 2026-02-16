<?php

namespace App\Http\Requests\Teacher;

use App\Strategies\Validation\Score\ScoreValidationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveManualGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $this->user()->can('update', $assessment);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'scores' => ['required', 'array'],
            'scores.*.question_id' => ['required', 'integer'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'scores.*.feedback' => ['nullable', 'string'],
            'teacher_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance to use ScoreValidationContext strategies.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $assessment = $this->route('assessment');

            if ($assessment) {
                $validationContext = new ScoreValidationContext;
                $validationContext->validate(
                    $validator,
                    $this->all(),
                    ['question_exists_in_assessment', 'score_not_exceeds_max'],
                    ['assessment' => $assessment]
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'scores.required' => __('validation.required', ['attribute' => __('messages.scores')]),
            'scores.array' => __('validation.array', ['attribute' => __('messages.scores')]),
            'scores.*.question_id.required' => __('validation.required', ['attribute' => __('messages.question')]),
            'scores.*.question_id.integer' => __('validation.integer', ['attribute' => __('messages.question')]),
            'scores.*.score.required' => __('validation.required', ['attribute' => __('messages.score')]),
            'scores.*.score.numeric' => __('validation.numeric', ['attribute' => __('messages.score')]),
            'scores.*.score.min' => __('validation.min.numeric', ['attribute' => __('messages.score'), 'min' => 0]),
        ];
    }
}
