<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use App\Enums\DeliveryMode;
use App\Strategies\Validation\QuestionValidationContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Trait AssessmentValidationRules
 *
 * Provides shared validation rules, prepareForValidation logic, and custom validator
 * configuration for Assessment form requests.
 * Eliminates duplication between StoreAssessmentRequest and UpdateAssessmentRequest.
 */
trait AssessmentValidationRules
{
    /**
     * Prepare the data for validation by normalizing field names.
     *
     * Transforms frontend field names to backend field names:
     * - scheduled_date -> scheduled_at
     * - duration -> duration_minutes
     * - Sets default coefficient to 1.0 if not provided (for create only)
     */
    protected function prepareAssessmentForValidation(): void
    {
        $data = [];

        if ($this->has('scheduled_date')) {
            $data['scheduled_at'] = $this->scheduled_date;
        }

        if ($this->has('duration')) {
            $data['duration_minutes'] = $this->duration;
        }

        if (! $this->has('coefficient') || $this->coefficient === null) {
            $data['coefficient'] = 1.0;
        }

        if ($this->has('type') && ! $this->has('delivery_mode')) {
            $data['delivery_mode'] = DeliveryMode::defaultForType($this->type)->value;
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get the base validation rules for assessment entities.
     *
     * @param  bool  $isUpdate  Whether this is an update operation (changes 'required' to 'sometimes')
     * @return array<string, array<int, mixed>>
     */
    protected function getAssessmentValidationRules(bool $isUpdate = false): array
    {
        $requiredOrSometimes = $isUpdate ? 'sometimes' : 'required';
        $deliveryMode = $this->input('delivery_mode');
        $isSupervised = $deliveryMode === DeliveryMode::Supervised->value;
        $isHomework = $deliveryMode === DeliveryMode::Homework->value;

        $rules = [
            'title' => [$requiredOrSometimes, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [$requiredOrSometimes, 'in:devoir,examen,tp,controle,projet'],
            'delivery_mode' => [$requiredOrSometimes, Rule::in(DeliveryMode::values())],
            'scheduled_at' => [$isSupervised ? $requiredOrSometimes : 'nullable', 'date'],
            'duration_minutes' => [$isSupervised ? $requiredOrSometimes : 'nullable', 'integer', 'min:1'],
            'due_date' => [$isHomework ? $requiredOrSometimes : 'nullable', 'date'],
            'coefficient' => [$requiredOrSometimes, 'numeric', 'min:0.01'],
            'max_file_size' => ['nullable', 'integer', 'min:1'],
            'allowed_extensions' => ['nullable', 'string', 'max:255'],
            'max_files' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'show_results_immediately' => ['sometimes', 'boolean'],
            'allow_late_submission' => ['sometimes', 'boolean'],
            'one_question_per_page' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.type' => ['required', 'string'],
            'questions.*.points' => ['required', 'numeric', 'min:0'],
            'questions.*.order_index' => ['required', 'integer'],
            'questions.*.choices' => ['sometimes', 'array'],
            'questions.*.choices.*.content' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
            'questions.*.choices.*.order_index' => ['required', 'integer'],
        ];

        if (! $isUpdate) {
            $rules['class_subject_id'] = ['required', 'exists:class_subjects,id'];
        }

        if ($isUpdate) {
            $rules['questions.*.id'] = ['nullable', 'integer'];
            $rules['questions.*.choices.*.id'] = ['nullable', 'integer'];
            $rules['deletedQuestionIds'] = ['sometimes', 'array'];
            $rules['deletedQuestionIds.*'] = ['integer'];
            $rules['deletedChoiceIds'] = ['sometimes', 'array'];
            $rules['deletedChoiceIds.*'] = ['integer'];
        }

        return $rules;
    }

    /**
     * Configure the validator to use QuestionValidationContext for custom question validation.
     *
     * @param  Validator  $validator  The validator instance
     */
    protected function configureAssessmentValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->has('questions') && is_array($this->questions)) {
                $validationContext = new QuestionValidationContext;
                $validationContext->validateQuestions($validator, $this->questions);
            }
        });
    }

    /**
     * Get custom validation messages for assessment entities.
     *
     * @param  bool  $isUpdate  Whether this is an update operation (affects message keys)
     * @return array<string, string>
     */
    protected function getAssessmentValidationMessages(bool $isUpdate = false): array
    {
        $messages = [
            'title.string' => __('validation.string', ['attribute' => __('messages.assessment_title')]),
            'type.in' => __('validation.in', ['attribute' => __('messages.assessment_type')]),
            'delivery_mode.in' => __('validation.in', ['attribute' => __('messages.delivery_mode')]),
            'duration_minutes.min' => __('validation.min.numeric', ['attribute' => __('messages.duration'), 'min' => 1]),
            'due_date.date' => __('validation.date', ['attribute' => __('messages.due_date')]),
            'coefficient.min' => __('validation.min.numeric', ['attribute' => __('messages.coefficient'), 'min' => 0.01]),
        ];

        if (! $isUpdate) {
            $messages['class_subject_id.required'] = __('validation.required', ['attribute' => __('messages.class_subject')]);
            $messages['title.required'] = __('validation.required', ['attribute' => __('messages.assessment_title')]);
            $messages['type.required'] = __('validation.required', ['attribute' => __('messages.assessment_type')]);
            $messages['delivery_mode.required'] = __('validation.required', ['attribute' => __('messages.delivery_mode')]);
            $messages['scheduled_at.required'] = __('validation.required', ['attribute' => __('messages.scheduled_date')]);
            $messages['duration_minutes.required'] = __('validation.required', ['attribute' => __('messages.duration')]);
            $messages['due_date.required'] = __('validation.required', ['attribute' => __('messages.due_date')]);
            $messages['coefficient.required'] = __('validation.required', ['attribute' => __('messages.coefficient')]);
        }

        return $messages;
    }
}
