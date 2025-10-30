<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamGroupService;
use App\Http\Requests\Exam\AssignExamRequest;
use App\Services\Exam\ExamAssignmentService;
use App\Http\Requests\Exam\GetExamResultsRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for managing individual student assignments to exams.
 * 
 * This controller handles:
 * - Displaying assignment forms
 * - Assigning exams to individual students
 * - Viewing assignment lists
 * - Removing student assignments
 */
class AssignmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamAssignmentService $examAssignmentService,
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Display the form to assign an exam to students.
     *
     * @param Exam $exam The exam instance to be assigned.
     * @return Response The response containing the assignment form view.
     */
    public function showAssignForm(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);

        $availableGroups = $this->examGroupService->getAvailableGroupsForExam($exam);

        $legacyAssignments = $this->examAssignmentService->getAssignmentFormData($exam);

        return Inertia::render('Exam/Assign', [
            'exam' => $exam,
            'assignedGroups' => $assignedGroups,
            'availableGroups' => $availableGroups,
            'students' => $legacyAssignments['students'],
        ]);
    }

    /**
     * Assigns the specified exam to selected students.
     *
     * Handles the assignment of an exam to students based on the validated request data.
     *
     * @param AssignExamRequest $request The validated request containing assignment details.
     * @param Exam $exam The exam instance to be assigned.
     * @return RedirectResponse Redirects back with a status message upon completion.
     */
    public function assignToStudents(AssignExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $result = $this->examAssignmentService->assignExamToStudents(
            $exam,
            $request->validated()['student_ids']
        );

        $message = "Assignation terminée : {$result['assigned_count']} nouveaux étudiants assignés";
        if ($result['already_assigned_count'] > 0) {
            $message .= " ({$result['already_assigned_count']} déjà assignés)";
        }

        return $this->redirectWithSuccess('exams.show', $message, ['exam' => $exam->id]);
    }

    /**
     * Display the assignments related to the specified exam.
     *
     * @param Exam $exam The exam instance for which assignments are to be shown.
     * @param GetExamResultsRequest $request The request containing parameters for fetching exam results.
     * @return Response The HTTP response containing the assignments data.
     */
    public function showAssignments(Exam $exam, GetExamResultsRequest $request): Response
    {
        $this->authorize('view', $exam);

        $params = $request->validatedWithDefaults();
        $data = $this->examAssignmentService->getPaginatedAssignments($exam, $params);

        return Inertia::render('Exam/Assignments', $data);
    }

    /**
     * Removes the assignment of the specified user from the given exam.
     *
     * @param Exam $exam The exam instance from which the user assignment will be removed.
     * @param User $user The user whose assignment is to be removed from the exam.
     * @return RedirectResponse Redirects back to the previous page or a specified route after removal.
     */
    public function removeAssignment(Exam $exam, User $user): RedirectResponse
    {
        $this->authorize('update', $exam);

        $assignment = $exam->assignments()->where('student_id', $user->id)->first();

        if (!$assignment) {
            return $this->redirectWithError(
                'exams.show',
                "Cet étudiant n'est pas assigné à cet examen.",
                ['exam' => $exam->id]
            );
        }

        $assignment->delete();

        return $this->redirectWithSuccess(
            'exams.show',
            "Assignation de {$user->name} supprimée avec succès.",
            ['exam' => $exam->id]
        );
    }
}
