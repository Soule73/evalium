<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AssignToGroupsRequest;
use App\Http\Requests\Exam\GetExamResultsRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Exam;
use App\Models\Group;
use App\Repositories\AssignmentRepository;
use App\Services\Core\ExamStatsService;
use App\Services\Exam\ExamAssignmentService;
use App\Services\Exam\ExamGroupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamGroupService $examGroupService,
        private ExamAssignmentService $examAssignmentService,
        private ExamStatsService $examStatsService,
        private AssignmentRepository $assignmentRepository
    ) {}

    /**
     * Display the form to assign an exam to groups.
     *
     * @param  Exam  $exam  The exam instance to be assigned.
     * @return Response The response containing the assignment form view.
     */
    public function showAssignForm(Exam $exam): Response
    {
        $this->authorize('assign', $exam);

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
     * @param  Exam  $exam  The exam instance for which assignments are to be shown.
     * @param  GetExamResultsRequest  $request  The request containing parameters for fetching exam results.
     * @return Response The HTTP response containing the assignments data.
     */
    public function showAssignments(Exam $exam, GetExamResultsRequest $request): Response
    {

        $params = $request->validatedWithDefaults();

        $assignments = $this->assignmentRepository->getPaginatedAssignments(
            $exam,
            $params['per_page'] ?? 10,
            $params['search'] ?? null,
            $params['status'] ?? null
        );

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);

        $stats = $this->examStatsService->calculateExamStatsWithGroups($exam, $assignedGroups);

        return Inertia::render('Exam/Assignments', [
            'exam' => $exam,
            'assignments' => $assignments,
            'stats' => $stats,
            'assignedGroups' => $assignedGroups,
        ]);
    }

    /**
     * Assign the specified exam to one or multiple groups.
     *
     * @param  AssignToGroupsRequest  $request  The request containing the group IDs.
     * @param  Exam  $exam  The exam instance to be assigned.
     * @return RedirectResponse Redirects back with a status message upon completion.
     */
    public function assignToGroups(AssignToGroupsRequest $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validated();

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
     * @param  Exam  $exam  The exam instance.
     * @param  Group  $group  The group instance.
     * @return RedirectResponse Redirects back with a status message.
     */
    public function removeFromGroup(Exam $exam, Group $group): RedirectResponse
    {
        $this->authorize('update', $exam);

        $removed = $this->examGroupService->removeExamFromGroup($exam, $group);

        if ($removed) {
            return $this->flashSuccess(__('messages.group_removed_from_exam'));
        }

        return $this->flashError(__('messages.unable_to_remove_group'));
    }

    /**
     * Show group details with students and their exam status.
     *
     * @param  Exam  $exam  The exam instance.
     * @param  Group  $group  The group instance.
     * @param  Request  $request  The request instance.
     * @return Response The response containing group details.
     */
    public function showGroup(Exam $exam, Group $group, Request $request): Response
    {
        $this->authorize('view', $exam);

        $exam->loadMissing('questions');
        $group->loadMissing('level')
            ->loadCount(['students as active_students_count' => function ($query) {
                $query->wherePivot('is_active', true);
            }]);

        $perPage = $request->input('per_page', 10);
        $filterStatus = $request->input('filter_status');
        $search = $request->input('search');

        $assignments = $this->assignmentRepository->getGroupStudentsWithAssignments(
            $exam,
            $group,
            $perPage,
            $search,
            $filterStatus
        );

        $stats = $this->examStatsService->calculateGroupStats($exam, $group);

        return Inertia::render('Exam/GroupDetails', [
            'exam' => $exam,
            'group' => $group,
            'assignments' => $assignments,
            'stats' => $stats,
        ]);
    }
}
