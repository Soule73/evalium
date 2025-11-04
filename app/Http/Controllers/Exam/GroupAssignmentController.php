<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\Group;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamGroupService;
use App\Services\Exam\ExamAssignmentService;
use App\Http\Requests\Exam\GetExamResultsRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for managing group assignments to exams.
 * 
 * SIMPLIFIED ARCHITECTURE: Group-based assignment ONLY
 * Individual student assignments are no longer supported.
 * 
 * This controller handles:
 * - Displaying assignment forms (groups only)
 * - Assigning exams to groups of students
 * - Viewing assignment lists with statistics
 * - Removing group assignments
 */
class GroupAssignmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamGroupService $examGroupService,
        private ExamAssignmentService $examAssignmentService
    ) {}

    /**
     * Display the form to assign an exam to groups.
     *
     * @param Exam $exam The exam instance to be assigned.
     * @return Response The response containing the assignment form view.
     */
    public function showAssignForm(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);
        $availableGroups = $this->examGroupService->getAvailableGroupsForExam($exam);

        return Inertia::render('Exam/Assign', [
            'exam' => $exam,
            'assignedGroups' => $assignedGroups,
            'availableGroups' => $availableGroups,
        ]);
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
     * Assign the specified exam to one or multiple groups.
     * 
     * Delegates to ExamGroupService to create exam-group associations
     * and individual student assignments.
     *
     * @param Request $request The request containing the group IDs.
     * @param Exam $exam The exam instance to be assigned.
     * @return RedirectResponse Redirects back with a status message upon completion.
     */
    public function assignToGroups(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'required|exists:groups,id',
        ]);

        $result = $this->examGroupService->assignExamToGroups(
            $exam,
            $validated['group_ids']
        );

        $message = __('messages.groups_assigned_to_exam', ['count' => $result['assigned_count']]);
        if ($result['already_assigned_count'] > 0) {
            $message .= ' ' . __('messages.groups_already_assigned', ['already_assigned' => $result['already_assigned_count']]);
        }

        return $this->redirectWithSuccess('exams.show', $message, ['exam' => $exam->id]);
    }

    /**
     * Remove the exam assignment from a group.
     * 
     * Delegates to ExamGroupService to delete the exam-group association
     * and cascade delete related student assignments.
     *
     * @param Exam $exam The exam instance.
     * @param int $groupId The group ID.
     * @return RedirectResponse Redirects back with a status message.
     */
    public function removeFromGroup(Exam $exam, int $groupId): RedirectResponse
    {
        $this->authorize('update', $exam);

        $group = Group::findOrFail($groupId);

        $removed = $this->examGroupService->removeExamFromGroup($exam, $group);

        if ($removed) {
            return $this->flashSuccess(__('messages.group_removed_from_exam'));
        }

        return $this->flashError(__('messages.unable_to_remove_group'));
    }

    /**
     * Show group details with students and their exam status.
     *
     * @param Exam $exam The exam instance.
     * @param Group $group The group instance.
     * @param Request $request The request instance.
     * @return Response The response containing group details.
     */
    public function showGroupShow(Exam $exam, Group $group, Request $request): Response
    {
        $this->authorize('view', $exam);

        $exam->load('questions');

        $params = [
            'per_page' => $request->input('per_page', 10),
            'filter_status' => $request->input('filter_status'),
            'search' => $request->input('search'),
            'page' => $request->input('page', 1),
        ];

        $data = $this->examGroupService->getGroupDetailsWithAssignments($exam, $group, $params);

        return Inertia::render('Exam/GroupDetails', [
            'exam' => $exam,
            'group' => $group,
            'assignments' => $data['assignments'],
            'stats' => $data['stats']
        ]);
    }
}
