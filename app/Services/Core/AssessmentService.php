<?php

namespace App\Services\Core;

use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Assessment Service - Manage assessments (exams, devoirs, tp, etc.)
 *
 * Single Responsibility: Handle assessment CRUD operations
 */
class AssessmentService
{
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
                'coefficient' => $data['coefficient'],
                'duration_minutes' => $data['duration_minutes'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'is_published' => $data['is_published'] ?? false,
            ]);

            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $question = $assessment->questions()->create([
                        'content' => $questionData['content'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                        'order_index' => $questionData['order_index'] ?? 0,
                    ]);

                    if (isset($questionData['choices']) && is_array($questionData['choices'])) {
                        foreach ($questionData['choices'] as $choiceData) {
                            $question->choices()->create([
                                'content' => $choiceData['content'],
                                'is_correct' => $choiceData['is_correct'] ?? false,
                                'order_index' => $choiceData['order_index'] ?? 0,
                            ]);
                        }
                    }
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
            $updateData = array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? null,
                'coefficient' => $data['coefficient'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'is_published' => $data['is_published'] ?? null,
            ], fn ($value) => $value !== null);

            if (isset($data['coefficient']) && $data['coefficient'] <= 0) {
                throw new InvalidArgumentException('Coefficient must be greater than 0');
            }

            $assessment->update($updateData);

            return $assessment->fresh(['questions.choices']);
        });
    }

    /**
     * Delete an assessment (soft delete)
     */
    public function deleteAssessment(Assessment $assessment): bool
    {
        if ($assessment->assignments()->exists()) {
            throw new InvalidArgumentException('Cannot delete assessment with existing student assignments');
        }

        return $assessment->delete();
    }

    /**
     * Get all assessments for a class-subject
     */
    public function getAssessmentsForClassSubject(ClassSubject $classSubject): Collection
    {
        return Assessment::where('class_subject_id', $classSubject->id)
            ->with(['questions.choices', 'assignments'])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Get assessments by type
     */
    public function getAssessmentsByType(int $classSubjectId, string $type): Collection
    {
        return Assessment::where('class_subject_id', $classSubjectId)
            ->where('type', $type)
            ->with(['questions.choices'])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Publish an assessment (make it visible to students)
     */
    public function publishAssessment(Assessment $assessment): Assessment
    {
        $assessment->update(['is_published' => true]);

        return $assessment->fresh();
    }

    /**
     * Unpublish an assessment
     */
    public function unpublishAssessment(Assessment $assessment): Assessment
    {
        $assessment->update(['is_published' => false]);

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

            foreach ($assessment->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->assessment_id = $newAssessment->id;
                $newQuestion->save();

                foreach ($question->choices as $choice) {
                    $newChoice = $choice->replicate();
                    $newChoice->question_id = $newQuestion->id;
                    $newChoice->save();
                }
            }

            return $newAssessment->load(['questions.choices']);
        });
    }

    /**
     * Validate assessment data
     */
    private function validateAssessmentData(array $data): void
    {
        $required = ['class_subject_id', 'title', 'type', 'coefficient', 'duration_minutes'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if ($data['coefficient'] <= 0) {
            throw new InvalidArgumentException('Coefficient must be greater than 0');
        }

        if ($data['duration_minutes'] <= 0) {
            throw new InvalidArgumentException('Duration must be greater than 0');
        }

        $validTypes = ['devoir', 'examen', 'tp', 'controle', 'projet'];
        if (! in_array($data['type'], $validTypes)) {
            throw new InvalidArgumentException('Invalid assessment type');
        }
    }
}
