<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeStudentGroupRequest;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\EditUserRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use App\Services\Core\ExamQueryService;
use App\Services\Student\StudentAssignmentQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        public readonly UserManagementService $userService,
        public readonly ExamQueryService $examQueryService,
        public readonly StudentAssignmentQueryService $studentAssignmentQueryService
    ) {}

    /**
     * Display a listing of the users.
     *
     * Delegates to UserManagementService to load paginated users with filtering.
     * Restricts admin visibility based on user role (super_admin can see all).
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request instance.
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $filters = $request->only(['search', 'role', 'per_page', 'status', 'include_deleted']);

        if (! $currentUser->hasRole('super_admin')) {
            $filters['exclude_roles'] = ['admin', 'super_admin'];
        }

        $users = $this->userService->getUserWithPagination($filters, 10, $currentUser);

        $availableRoles = $this->userService->getAvailableRoles($currentUser);
        $groups = $this->userService->getActiveGroupsWithLevels();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $availableRoles,
            'groups' => $groups,
            'canManageAdmins' => $currentUser->hasRole('super_admin'),
            'canDeleteUsers' => $currentUser->can('delete users'),
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * Delegates to UserManagementService to create user with role assignment.
     *
     * @param  \App\Http\Requests\CreateUserRequest  $request  The validated request containing user data.
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        $this->authorize('create', User::class);

        try {
            $validated = $request->validated();

            $this->userService->store($validated);

            return $this->redirectWithSuccess('users.index', __('messages.user_created'));
        } catch (\Exception $e) {
            Log::error('Error creating user', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Display the specified teacher's details with exams.
     *
     * Delegates to ExamQueryService to load paginated exams for teacher.
     * Uses eager loading for optimization.
     *
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance.
     * @param  \App\Models\User  $user  The user instance representing the teacher.
     * @return \Illuminate\Http\Response
     */
    public function showTeacher(Request $request, User $user)
    {
        $this->authorize('view', $user);

        if (! $this->userService->isTeacher($user)) {
            return $this->flashError(__('messages.unauthorized'));
        }

        $perPage = $request->input('per_page', 10);
        $status = null;

        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status') === '1' ? true : false;
        }

        $search = $request->input('search');

        $this->userService->ensureRolesLoaded($user);

        $exams = $this->examQueryService->getExams($user->id, $perPage, $status, $search);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Users/ShowTeacher', [
            'user' => $user,
            'exams' => $exams,
            'canDelete' => $currentUser->hasRole('super_admin'),
            'canToggleStatus' => $currentUser->can('toggle user status'),
        ]);
    }

    /**
     * Display the specified student details with groups and exam assignments.
     *
     * Delegates to ExamQueryService to load paginated assignments.
     * Uses eager loading for groups, levels, and exams optimization.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request instance.
     * @param  \App\Models\User  $user  The user instance representing the student.
     * @return \Illuminate\Http\Response
     */
    public function showStudent(Request $request, User $user)
    {
        $this->authorize('view', $user);

        if (! $this->userService->isStudent($user)) {
            return $this->flashError(__('messages.unauthorized'));
        }

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status') ? $request->input('status') : null;
        $search = $request->input('search');

        $this->userService->loadStudentGroupsWithExams($user);

        $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudent($user, $perPage, $status, $search);

        $availableGroups = $this->userService->getActiveGroupsWithLevels();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Users/ShowStudent', [
            'user' => $user,
            'examsAssignments' => $assignments,
            'availableGroups' => $availableGroups,
            'canDelete' => $currentUser->hasRole('super_admin'),
            'canToggleStatus' => $currentUser->can('toggle user status'),
        ]);
    }

    /**
     * Update the specified user's information.
     *
     * Validates permissions (only super_admin can edit admins).
     * Delegates to UserManagementService to update user data.
     *
     * @param  \App\Http\Requests\EditUserRequest  $request  The validated request containing user update data.
     * @param  \App\Models\User  $user  The user instance to be updated.
     * @return \Illuminate\Http\Response
     */
    public function update(EditUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->hasRole(['admin', 'super_admin']) && ! $auth->hasRole('super_admin')) {
            return $this->flashError(__('messages.unauthorized'));
        }

        try {
            $validated = $request->validated();

            $this->userService->update($user, $validated);

            return $this->flashSuccess(__('messages.user_updated'));
        } catch (\Exception $e) {
            Log::error('Error updating user', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove the specified user from storage (soft delete).
     *
     * Validates permissions and prevents self-deletion.
     * Only super_admin can delete admin users.
     *
     * @param  \App\Models\User  $user  The user instance to be deleted.
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError(__('messages.unauthorized'));
        }

        if ($user->hasRole(['admin', 'super_admin']) && ! $auth->hasRole('super_admin')) {
            return $this->flashError(__('messages.unauthorized'));
        }

        $this->userService->delete($user);

        return $this->redirectWithSuccess('users.index', __('messages.user_deleted'));
    }

    /**
     * Toggle the status (active/inactive) of the specified user.
     *
     * Validates permissions and prevents self-modification.
     * Only super_admin can modify admin status.
     *
     * @param  \App\Models\User  $user  The user instance whose status will be toggled.
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);

        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError(__('messages.unauthorized'));
        }

        if ($user->hasRole(['admin', 'super_admin']) && ! $auth->hasRole('super_admin')) {
            return $this->flashError(__('messages.unauthorized'));
        }

        $this->userService->toggleStatus($user);

        $messageKey = $user->is_active ? 'messages.user_activated' : 'messages.user_deactivated';

        return $this->redirectWithSuccess('users.index', __($messageKey));
    }

    /**
     * Change the group of a student.
     *
     * Delegates to UserManagementService to reassign student to new group.
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStudentGroup(ChangeStudentGroupRequest $request, User $user)
    {
        $this->authorize('update', $user);

        if (! $this->userService->isStudent($user)) {
            return $this->flashError(__('messages.unauthorized'));
        }

        try {
            $this->userService->changeStudentGroup($user, $request->validated()['group_id']);

            return $this->redirectWithSuccess('users.show.student', __('messages.group_changed'), ['user' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Error changing group', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Restore a soft-deleted user.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        $this->authorize('restore', User::class);

        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return $this->redirectWithSuccess('users.index', __('messages.user_restored'));
        } catch (\Exception $e) {
            Log::error('Error restoring user', ['user_id' => $id, 'error' => $e->getMessage()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Permanently delete a user (force delete).
     *
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(int $id)
    {
        $this->authorize('forceDelete', User::class);

        try {
            $user = User::withTrashed()->findOrFail($id);

            /** @var \App\Models\User $auth */
            $auth = Auth::user();

            if ($user->id === $auth->id) {
                return $this->flashError(__('messages.unauthorized'));
            }

            $user->forceDelete();

            return $this->redirectWithSuccess('users.index', __('messages.user_deleted'));
        } catch (\Exception $e) {
            Log::error('Error permanently deleting user', ['user_id' => $id, 'error' => $e->getMessage()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }
}
