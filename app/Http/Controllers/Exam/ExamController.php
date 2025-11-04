<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\Core\ExamCrudService;
use App\Services\Core\ExamQueryService;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamGroupService;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Exam Controller - Unified controller for exam management (NON-STUDENTS)
 * 
 * ARCHITECTURE:
 * - Student/ExamController: Strict student-only actions (take, submit, abandon)
 * - ExamController (this file): All other permission-based actions
 * 
 * Permissions are verified by MIDDLEWARE in routes/web.php
 * No need to re-verify in controller (no duplication)
 * 
 * POLICIES verify access at model level (ownership, etc.)
 * 
 * Responsibilities:
 * - Delegate all business logic to services (ExamCrudService, ExamQueryService)
 * - Handle HTTP request/response cycle
 * - Authorize actions via policies
 * - Return Inertia responses with proper data structure
 */
class ExamController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamQueryService $examQueryService,
        private ExamCrudService $examCrudService,
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Display list of exams - Adapted based on user permissions
     * 
     * Middleware already verifies 'view exams' permission.
     * Policy verifies model-level access.
     *
     * @param Request $request The HTTP request
     * @return Response Inertia response with paginated exams
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        $status = null;
        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status') === '1' ? true : false;
        }

        $search = $request->input('search');

        $this->authorize('viewAny', Exam::class);

        $exams = $this->examQueryService->getExamsForTeacher($user->id, $perPage, $status, $search);

        return Inertia::render('Exam/Index', [
            'exams' => $exams
        ]);
    }

    /**
     * Display the form for creating a new exam
     *
     * @return Response Inertia response with create form
     */
    public function create(): Response
    {
        $this->authorize('create', Exam::class);

        return Inertia::render('Exam/Create');
    }

    /**
     * Store a newly created exam in storage
     *
     * Delegates exam creation to ExamCrudService which handles:
     * - Exam model creation
     * - Questions creation with choices
     * - Database transaction management
     *
     * @param StoreExamRequest $request Validated request with exam data
     * @return RedirectResponse Redirects to exam show page on success
     */
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);

        try {
            $exam = $this->examCrudService->create($request->validated());

            return $this->redirectWithSuccess(
                'exams.show',
                'Exam created successfully!',
                ['exam' => $exam->id]
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                null,
                "Error creating exam: " . $e->getMessage()
            );
        }
    }

    /**
     * Display the specified exam details
     *
     * Loads exam with assigned groups for display.
     * Uses ExamQueryService to prepare data structure.
     *
     * @param Exam $exam The exam instance to display
     * @return Response Inertia response with exam details
     */
    public function show(Exam $exam): Response
    {
        $this->authorize('view', $exam);

        $data = $this->examQueryService->getExamForDisplay($exam, $this->examGroupService);

        return Inertia::render('Exam/Show', $data);
    }

    /**
     * Show the form for editing the specified exam
     *
     * Loads exam with questions and choices for editing.
     *
     * @param Exam $exam The exam instance to edit
     * @return Response Inertia response with edit form
     */
    public function edit(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $exam = $this->examQueryService->getExamForEdit($exam);

        return Inertia::render('Exam/Edit', [
            'exam' => $exam
        ]);
    }

    /**
     * Update the specified exam in storage
     *
     * Delegates update logic to ExamCrudService which handles:
     * - Exam model update
     * - Questions and choices update/creation/deletion
     * - Database transaction management
     *
     * @param UpdateExamRequest $request Validated request with update data
     * @param Exam $exam The exam instance to update
     * @return RedirectResponse Redirects to exam show page on success
     */
    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        try {
            $exam = $this->examCrudService->update($exam, $request->validated());

            return $this->redirectWithSuccess(
                'exams.show',
                'Exam updated successfully!',
                ['exam' => $exam->id]
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                null,
                "Error updating exam: " . $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified exam from storage
     *
     * Delegates deletion to ExamCrudService which handles:
     * - Cascade deletion of questions, choices, answers
     * - Deletion of exam assignments
     * - Database transaction management
     *
     * @param Exam $exam The exam instance to be deleted
     * @return RedirectResponse Redirects to exams index on success
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        try {
            $this->examCrudService->delete($exam);

            return $this->redirectWithSuccess(
                'exams.index',
                'Exam deleted successfully!'
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                null,
                "Error deleting exam: " . $e->getMessage()
            );
        }
    }

    /**
     * Duplicate the specified exam
     *
     * Creates a complete copy of the exam including:
     * - All questions with their choices
     * - Marks new exam as inactive by default
     * - Appends " (Copy)" to title
     *
     * @param Exam $exam The exam to be duplicated
     * @return RedirectResponse Redirects to edit page of new exam
     */
    public function duplicate(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $newExam = $this->examCrudService->duplicate($exam);

            return $this->redirectWithSuccess(
                'exams.edit',
                'Exam duplicated successfully! You can now modify it.',
                ['exam' => $newExam->id]
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                null,
                "Error duplicating exam: " . $e->getMessage()
            );
        }
    }

    /**
     * Toggle the active status of the specified exam
     *
     * Switches between active and inactive states.
     * Used to quickly enable/disable exams without editing.
     *
     * @param Exam $exam The exam instance whose status will be toggled
     * @return RedirectResponse Redirects back with flash message
     */
    public function toggleActive(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        try {
            $exam = $this->examCrudService->toggleStatus($exam);

            $status = $exam->is_active ? 'activated' : 'deactivated';

            return $this->flashSuccess("Exam {$status} successfully!");
        } catch (\Exception $e) {
            return $this->flashError("Error changing exam status: " . $e->getMessage());
        }
    }
}
