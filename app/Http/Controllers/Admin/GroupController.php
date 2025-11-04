<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Group;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\Admin\GroupService;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Http\Requests\Admin\AssignStudentsToGroupRequest;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    use HasFlashMessages;

    public function __construct(
        private readonly GroupService $groupService
    ) {}

    /**
     * Display a listing of groups with filters.
     * 
     * Delegates to GroupService to load paginated groups with levels.
     * Supports filtering by search, level_id, and is_active.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'level_id', 'is_active']);
        $groups = $this->groupService->getGroupsWithPagination($filters, 15);
        $levels = $this->groupService->getLevelsForFilters();

        return Inertia::render('Admin/Groups/Index', [
            'groups' => $groups,
            'filters' => $filters,
            'levels' => $levels,
        ]);
    }

    /**
     * Show the form for creating a new group.
     * 
     * Delegates to GroupService to load form data (levels).
     *
     * @return Response
     */
    public function create(): Response
    {
        $formData = $this->groupService->getFormData();
        return Inertia::render('Admin/Groups/Create', $formData);
    }

    /**
     * Store a newly created group.
     * 
     * Delegates to GroupService to create group with validated data.
     *
     * @param StoreGroupRequest $request
     * @return RedirectResponse
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        try {
            $this->groupService->createGroup($request->validated());
            return $this->redirectWithSuccess('groups.index', __('messages.group_created'));
        } catch (\Exception $e) {
            Log::error('Error creating group', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Display the specified group with students.
     *
     * @param Group $group
     * @return Response
     */
    public function show(Group $group): Response
    {
        $group->load(['level', 'students' => function ($query) {
            $query->withPivot(['enrolled_at', 'left_at', 'is_active'])
                ->orderBy('group_student.enrolled_at', 'desc');
        }]);

        return Inertia::render('Admin/Groups/Show', [
            'group' => $group,
        ]);
    }

    /**
     * Show the form for editing a group.
     *
     * @param Group $group
     * @return Response
     */
    public function edit(Group $group): Response
    {
        $group->load('level');
        $formData = $this->groupService->getFormData();
        return Inertia::render('Admin/Groups/Edit', array_merge($formData, [
            'group' => $group,
        ]));
    }

    /**
     * Update the specified group.
     * 
     * Delegates to GroupService to update group with validated data.
     *
     * @param UpdateGroupRequest $request
     * @param Group $group
     * @return RedirectResponse
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupService->updateGroup($group, $request->validated());
            return $this->redirectWithSuccess('groups.show', __('messages.group_updated'), ['group' => $group->id]);
        } catch (\Exception $e) {
            Log::error('Error updating group', ['group_id' => $group->id, 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove the specified group.
     * 
     * Delegates to GroupService to delete group.
     *
     * @param Group $group
     * @return RedirectResponse
     */
    public function destroy(Group $group): RedirectResponse
    {
        try {
            $this->groupService->deleteGroup($group);
            return $this->redirectWithSuccess('groups.index', __('messages.group_deleted'));
        } catch (\Exception $e) {
            Log::error('Error deleting group', ['group_id' => $group->id, 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Show form to assign students to group.
     *
     * @param Group $group
     * @return Response
     */
    public function assignStudents(Group $group): Response
    {
        $availableStudents = $this->groupService->getAvailableStudents();
        return Inertia::render('Admin/Groups/AssignStudents', [
            'group' => $group,
            'availableStudents' => $availableStudents,
        ]);
    }

    /**
     * Assign students to the specified group.
     * 
     * Delegates to GroupService to create student-group associations.
     *
     * @param AssignStudentsToGroupRequest $request
     * @param Group $group
     * @return RedirectResponse
     */
    public function storeStudents(AssignStudentsToGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $result = $this->groupService->assignStudentsToGroup($group, $request->validated()['student_ids']);

            return $this->redirectWithSuccess('groups.show', __('messages.students_assigned', ['count' => $result['assigned_count']]), ['group' => $group->id]);
        } catch (\Exception $e) {
            Log::error('Error assigning students', ['group_id' => $group->id, 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove a student from the group.
     *
     * @param Group $group
     * @param User $student
     * @return RedirectResponse
     */
    public function removeStudent(Group $group, User $student): RedirectResponse
    {
        try {
            $this->groupService->removeStudentFromGroup($group, $student);
            return $this->redirectWithSuccess('groups.show', __('messages.student_removed'), ['group' => $group->id]);
        } catch (\Exception $e) {
            Log::error('Error removing student', ['group_id' => $group->id, 'student_id' => $student->id, 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Activate multiple groups at once.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkActivate(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:groups,id',
        ]);

        try {
            $result = $this->groupService->bulkActivate($request->input('ids'));

            return $this->redirectWithSuccess('groups.index', __('messages.groups_activated', ['count' => $result['activated_count']]));
        } catch (\Exception $e) {
            Log::error('Error activating groups', ['ids' => $request->input('ids'), 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Deactivate multiple groups at once.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:groups,id',
        ]);

        try {
            $result = $this->groupService->bulkDeactivate($request->input('ids'));

            return $this->redirectWithSuccess('groups.index', __('messages.groups_deactivated', ['count' => $result['deactivated_count']]));
        } catch (\Exception $e) {
            Log::error('Error deactivating groups', ['ids' => $request->input('ids'), 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove multiple students from a group at once.
     *
     * @param Request $request
     * @param Group $group
     * @return RedirectResponse
     */
    public function bulkRemoveStudents(Request $request, Group $group): RedirectResponse
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = $this->groupService->bulkRemoveStudentsFromGroup($group, $request->input('student_ids'));

            return $this->redirectWithSuccess('groups.show', __('messages.students_removed', ['count' => $result['removed_count']]), ['group' => $group->id]);
        } catch (\Exception $e) {
            Log::error('Error removing students', ['group_id' => $group->id, 'error' => $e->getMessage()]);
            return $this->flashError(__('messages.operation_failed'));
        }
    }
}
