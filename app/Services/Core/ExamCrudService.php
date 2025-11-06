<?php

namespace App\Services\Core;

use App\Models\Exam;
use App\Repositories\AssignmentRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Exam CRUD Service - Handle exam creation, update, and deletion
 *
 * Single Responsibility: Manage exam lifecycle (CRUD operations)
 * Dependencies: QuestionManagementService for question-related operations
 */
class ExamCrudService
{
    public function __construct(
        private readonly QuestionManagementService $questionService,
        private readonly AssignmentRepository $assignmentRepository
    ) {}

    /**
     * Create a new exam with its questions
     */
    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'teacher_id' => Auth::id(),
            ]);

            if (isset($data['questions']) && is_array($data['questions'])) {
                $this->questionService->createQuestionsForExam($exam, $data['questions']);
            }

            return $exam->load(['questions.choices']);
        });
    }

    /**
     * Update an existing exam
     */
    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $exam->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            if (isset($data['deletedQuestionIds']) && ! empty($data['deletedQuestionIds'])) {
                $this->questionService->deleteQuestionsById($exam, $data['deletedQuestionIds']);
            }

            if (isset($data['deletedChoiceIds']) && ! empty($data['deletedChoiceIds'])) {
                $this->questionService->deleteChoicesById($exam, $data['deletedChoiceIds']);
            }

            if (isset($data['questions']) && is_array($data['questions'])) {
                $this->questionService->updateQuestionsForExam($exam, $data['questions']);
            }

            return $exam->load(['questions.choices']);
        });
    }

    /**
     * Delete an exam and all its related data using bulk operations
     */
    public function delete(Exam $exam): bool
    {
        return DB::transaction(function () use ($exam) {
            $questionIds = $exam->questions()->pluck('id');

            if ($questionIds->isNotEmpty()) {
                $this->questionService->deleteBulk($questionIds);
            }

            $exam->assignments()->delete();

            return $exam->delete();
        });
    }

    /**
     * Duplicate an exam with all its questions and choices
     */
    public function duplicate(Exam $originalExam): Exam
    {
        return DB::transaction(function () use ($originalExam) {
            $examData = $originalExam->toArray();
            unset($examData['id'], $examData['created_at'], $examData['updated_at']);
            $examData['title'] = $examData['title'].' (Copie)';
            $examData['is_active'] = false;

            $newExam = Exam::create($examData);

            foreach ($originalExam->questions as $originalQuestion) {
                $this->questionService->duplicateQuestion($originalQuestion, $newExam);
            }

            return $newExam->load(['questions.choices']);
        });
    }

    /**
     * Toggle exam active status
     */
    public function toggleStatus(Exam $exam): Exam
    {
        $exam->update(['is_active' => ! $exam->is_active]);

        return $exam->fresh();
    }
}
