<?php

namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignStudentsToGroupRequest;
use App\Http\Requests\Admin\GroupBulkActionRequest;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Group;
use App\Models\User;
use App\Services\Admin\GroupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly GroupService $groupService
    ) {}

    /**
     * Display a listing of groups with filters.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return Response The response containing the groups list view.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Group::class);

        $filters = $request->only(['search', 'level_id', 'is_active']);

        $groups = $this->groupService->getGroupsWithPagination($filters, 15);

        $levels = $this->groupService->getLevelsForFilters();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'filters' => $filters,
            'levels' => $levels,
        ]);
    }

    /**
     * Show the form for creating a new group.
     *
     * @return Response The response containing the group creation form view.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        $formData = $this->groupService->getFormData();

        return Inertia::render('Groups/Create', $formData);
    }

    /**
     * Store a newly created group.
     *
     * Delegates to GroupService to create group with validated data.
     *
     * @param  StoreGroupRequest  $request  The validated request containing group data.
     * @return RedirectResponse Redirects to groups index on success.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        try {

            $this->groupService->createGroup($request->validated());

            return $this->redirectWithSuccess(
                'groups.index',
                __('messages.group_created')
            );
        } catch (\Exception $e) {

            Log::error('Error creating group', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Display the specified group with students.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Group  $group  The group instance to be displayed.
     */
    public function show(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $group->load('level');

        $filters = $request->only(['search', 'status']);

        $students = $this->groupService->getGroupStudentsWithPagination($group, $filters, 10);

        $statistics = $this->groupService->getGroupStatistics($group);

        return Inertia::render(
            'Groups/Show',
            [
                'group' => $group,
                'students' => $students,
                'filters' => $filters,
                'statistics' => $statistics,
            ]
        );
    }

    /**
     * Show the form for editing a group.
     *
     * @param  Group  $group  The group instance to be edited.
     */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);

        $group = $this->groupService->loadGroupWithLevel($group);

        $formData = $this->groupService->getFormData();

        return Inertia::render(
            'Groups/Edit',
            array_merge($formData, [
                'group' => $group,
            ])
        );
    }

    /**
     * Update the specified group.
     *
     * @param  UpdateGroupRequest  $request  The validated request containing group update data.
     * @param  Group  $group  The group instance to be updated.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupService->updateGroup($group, $request->validated());

            return $this->redirectWithSuccess(
                'groups.show',
                __('messages.group_updated'),
                ['group' => $group->id]
            );
        } catch (\Exception $e) {

            Log::error('Error updating group', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove the specified group.
     *
     * @param  Group  $group  The group instance to be deleted.
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        try {

            $this->groupService->deleteGroup($group);

            return $this->redirectWithSuccess(
                'groups.index',
                __('messages.group_deleted')
            );
        } catch (\Exception $e) {

            Log::error('Error deleting group', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Show form to assign students to group.
     *
     * @param  Group  $group  The group instance to assign students to.
     */
    public function assignStudents(Request $request, Group $group): Response
    {
        $this->authorize('manageStudents', $group);

        $filters = $request->only(['search']);

        $availableStudents = $this->groupService->getAvailableStudentsWithPagination($filters, 10);

        return Inertia::render('Groups/AddStudentsToGroup', [
            'group' => $group,
            'availableStudents' => $availableStudents,
            'filters' => $filters,
        ]);
    }

    /**
     * Assign students to the specified group.
     *
     * @param  AssignStudentsToGroupRequest  $request  The validated request containing student IDs.
     * @param  Group  $group  The group instance to assign students to.
     */
    public function storeStudents(AssignStudentsToGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $data = $request->validated();

            $result = $this->groupService->assignStudentsToGroup(
                $group,
                $data['student_ids']
            );

            return $this->redirectWithSuccess(
                'groups.show',
                __(
                    'messages.students_assigned',
                    ['count' => $result['assigned_count']]
                ),
                ['group' => $group->id]
            );
        } catch (\Exception $e) {

            Log::error('Error assigning students', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove a student from the group.
     *
     * @param  Group  $group  The group instance to remove the student from.
     * @param  User  $student  The student instance to be removed.
     */
    public function removeStudent(Group $group, User $student): RedirectResponse
    {
        $this->authorize('manageStudents', $group);

        try {
            $this->groupService->removeStudentFromGroup($group, $student);

            return $this->redirectWithSuccess(
                'groups.show',
                __('messages.student_removed'),
                ['group' => $group->id]
            );
        } catch (\Exception $e) {

            Log::error('Error removing student', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Activate multiple groups at once.
     *
     * @param  Request  $request  The incoming HTTP request.
     */
    public function bulkActivate(GroupBulkActionRequest $request): RedirectResponse
    {
        $this->authorize('update', Group::class);

        try {
            $result = $this->groupService->bulkActivate($request->input('ids'));

            return $this->redirectWithSuccess(
                'groups.index',
                __('messages.groups_activated', [
                    'count' => $result['activated_count'],
                ])
            );
        } catch (\Exception $e) {

            Log::error('Error activating groups', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Deactivate multiple groups at once.
     *
     * @param  Request  $request  The incoming HTTP request.
     */
    public function bulkDeactivate(GroupBulkActionRequest $request): RedirectResponse
    {
        $this->authorize('update', Group::class);

        try {
            $result = $this->groupService->bulkDeactivate($request->input('ids'));

            return $this->redirectWithSuccess(
                'groups.index',
                __('messages.groups_deactivated', [
                    'count' => $result['deactivated_count'],
                ])
            );
        } catch (\Exception $e) {

            Log::error('Error deactivating groups', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove multiple students from a group at once.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Group  $group  The group instance to remove students from.
     */
    public function bulkRemoveStudents(
        GroupBulkActionRequest $request,
        Group $group
    ): RedirectResponse {

        $this->authorize('manageStudents', $group);

        try {
            $result = $this->groupService->bulkRemoveStudentsFromGroup(
                $group,

                $request->input('student_ids')
            );

            return $this->redirectWithSuccess(
                'groups.show',
                __(
                    'messages.students_removed',
                    ['count' => $result['removed_count']]
                ),
                ['group' => $group->id]
            );
        } catch (\Exception $e) {

            Log::error('Error removing students', $e->getMessage());

            return $this->flashError(__('messages.operation_failed'));
        }
    }
}
