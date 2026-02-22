<?php

namespace App\Services\Core;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Exceptions\AssessmentException;
use App\Exceptions\ValidationException;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Assessment Service - Manage assessments (exams, devoirs, tp, etc.)
 *
 * Single Responsibility: Handle assessment CRUD operations
 */
class AssessmentService
{
    public function __construct(
        private readonly QuestionCrudService $questionCrudService,
        private readonly ChoiceManagementService $choiceManagementService,
        private readonly QuestionDuplicationService $questionDuplicationService
    ) {}

    /**
     * Create a new assessment for a class-subject
     */
    public function createAssessment(array $data): Assessment
    {
        $this->validateAssessmentData($data);

        return DB::transaction(function () use ($data) {
            $assessment = Assessment::create([
                'class_subject_id' => $data['class_subject_id'],
                'teacher_id' => $data['teacher_id'] ?? Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'delivery_mode' => $data['delivery_mode'] ?? DeliveryMode::defaultForType($data['type']),
                'coefficient' => $data['coefficient'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);

            $assessment->is_published = $data['is_published'] ?? false;
            $assessment->shuffle_questions = $data['shuffle_questions'] ?? false;
            $assessment->show_results_immediately = $data['show_results_immediately'] ?? true;
            $assessment->show_correct_answers = $data['show_correct_answers'] ?? false;
            $assessment->allow_late_submission = $data['allow_late_submission'] ?? false;
            $assessment->one_question_per_page = $data['one_question_per_page'] ?? false;
            $assessment->save();

            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $question = $this->questionCrudService->createQuestion($assessment, $questionData);
                    $this->choiceManagementService->createChoicesForQuestion($question, $questionData);
                }
            }

            return $assessment->load(['questions.choices']);
        });
    }

    /**
     * Update an existing assessment
     */
    public function updateAssessment(Assessment $assessment, array $data): Assessment
    {
        return DB::transaction(function () use ($assessment, $data) {
            $updatableFields = [
                'title',
                'description',
                'type',
                'delivery_mode',
                'coefficient',
                'duration_minutes',
                'scheduled_at',
                'due_date',
            ];

            $updateData = [];
            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['coefficient']) && $data['coefficient'] <= 0) {
                throw AssessmentException::invalidCoefficient();
            }

            $assessment->update($updateData);

            if (array_key_exists('is_published', $data)) {
                $assessment->is_published = $data['is_published'];
            }
            if (array_key_exists('shuffle_questions', $data)) {
                $assessment->shuffle_questions = $data['shuffle_questions'];
            }
            if (array_key_exists('show_results_immediately', $data)) {
                $assessment->show_results_immediately = $data['show_results_immediately'];
            }
            if (array_key_exists('show_correct_answers', $data)) {
                $assessment->show_correct_answers = $data['show_correct_answers'];
            }
            if (array_key_exists('allow_late_submission', $data)) {
                $assessment->allow_late_submission = $data['allow_late_submission'];
            }
            if (array_key_exists('one_question_per_page', $data)) {
                $assessment->one_question_per_page = $data['one_question_per_page'];
            }
            $assessment->save();

            if (isset($data['deletedChoiceIds']) && ! empty($data['deletedChoiceIds'])) {
                $this->choiceManagementService->deleteChoicesByIds($data['deletedChoiceIds']);
            }

            if (isset($data['deletedQuestionIds']) && ! empty($data['deletedQuestionIds'])) {
                $this->questionCrudService->deleteQuestionsById($assessment, $data['deletedQuestionIds']);
            }

            if (isset($data['questions']) && is_array($data['questions'])) {
                $this->updateQuestionsForAssessment($assessment, $data['questions']);
            }

            return $assessment->fresh(['questions.choices']);
        });
    }

    /**
     * Delete an assessment (soft delete)
     */
    public function deleteAssessment(Assessment $assessment): bool
    {
        if ($assessment->assignments()->exists()) {
            throw AssessmentException::hasExistingAssignments();
        }

        return $assessment->delete();
    }

    /**
     * Publish an assessment (make it visible to students)
     */
    public function publishAssessment(Assessment $assessment): Assessment
    {
        $assessment->is_published = true;
        $assessment->save();

        return $assessment->fresh();
    }

    /**
     * Unpublish an assessment
     */
    public function unpublishAssessment(Assessment $assessment): Assessment
    {
        $assessment->is_published = false;
        $assessment->save();

        return $assessment->fresh();
    }

    /**
     * Duplicate an assessment
     */
    public function duplicateAssessment(Assessment $assessment, array $overrides = []): Assessment
    {
        return DB::transaction(function () use ($assessment, $overrides) {
            $newAssessment = $assessment->replicate();
            $newAssessment->title = $overrides['title'] ?? ($assessment->title.' (Copy)');
            $newAssessment->is_published = false;
            $newAssessment->scheduled_at = $overrides['scheduled_at'] ?? null;
            $newAssessment->save();

            $this->questionDuplicationService->duplicateMultiple(
                $assessment->questions,
                $newAssessment
            );

            return $newAssessment->load(['questions.choices']);
        });
    }

    /**
     * Determine whether grading is allowed for a given assignment.
     *
     * Extracted from the Assessment model to keep business logic out of Eloquent models.
     *
     * - submitted_at set           → allowed (normal grading flow)
     * - submitted_at null + ended  → allowed with warning banner
     * - submitted_at null + active → blocked (student still has time)
     *
     * @return array{allowed: bool, reason: string, warning: string|null}
     */
    public function resolveGradingState(Assessment $assessment, AssessmentAssignment $assignment): array
    {
        if ($assignment->submitted_at !== null) {
            return [
                'allowed' => true,
                'reason' => 'submitted',
                'warning' => null,
            ];
        }

        if ($assessment->hasEnded()) {
            return [
                'allowed' => true,
                'reason' => 'not_submitted_assessment_ended',
                'warning' => 'grading_without_submission',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'assessment_still_running',
            'warning' => null,
        ];
    }

    /**
     * Validate assessment data
     */
    private function validateAssessmentData(array $data): void
    {
        $required = ['class_subject_id', 'title', 'type', 'coefficient'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw ValidationException::missingRequiredField($field);
            }
        }

        $deliveryMode = $data['delivery_mode'] ?? DeliveryMode::defaultForType($data['type']);

        if ($deliveryMode === DeliveryMode::Supervised->value || $deliveryMode === DeliveryMode::Supervised) {
            if (! isset($data['duration_minutes']) || $data['duration_minutes'] <= 0) {
                throw AssessmentException::invalidDuration();
            }
        }

        if ($data['coefficient'] <= 0) {
            throw AssessmentException::invalidCoefficient();
        }

        $validTypes = AssessmentType::values();
        if (! in_array($data['type'], $validTypes)) {
            throw AssessmentException::invalidType($data['type']);
        }
    }

    /**
     * Update questions for an assessment
     */
    private function updateQuestionsForAssessment(Assessment $assessment, array $questionsData): void
    {
        foreach ($questionsData as $questionData) {
            if (isset($questionData['id']) && is_numeric($questionData['id']) && $questionData['id'] > 0) {
                $question = $this->questionCrudService->updateQuestionById($assessment, $questionData['id'], $questionData);
                if ($question) {
                    $this->choiceManagementService->updateChoicesForQuestion($question, $questionData);
                }
            } else {
                $question = $this->questionCrudService->createQuestion($assessment, $questionData);
                $this->choiceManagementService->createChoicesForQuestion($question, $questionData);
            }
        }
    }
}
