<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\EditUserRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        public readonly UserManagementService $userService
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

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $availableRoles,
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

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Users/ShowTeacher', [
            'user' => $user,
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

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Users/ShowStudent', [
            'user' => $user,
            'canDelete' => $currentUser->hasRole('super_admin'),
            'canToggleStatus' => $currentUser->can('toggle user status'),
        ]);
    }

    /**
     * Update the specified user's information.
     *
     * Delegates to UserManagementService to update user data.
     *
     * @param  \App\Http\Requests\EditUserRequest  $request  The validated request containing user update data.
     * @param  \App\Models\User  $user  The user instance to be updated.
     * @return \Illuminate\Http\Response
     */
    public function update(EditUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

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
     * Delegates to UserManagementService to delete user.
     *
     * @param  \App\Models\User  $user  The user instance to be deleted.
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return $this->redirectWithSuccess('users.index', __('messages.user_deleted'));
    }

    /**
     * Toggle the status (active/inactive) of the specified user.
     *
     * Delegates to UserManagementService to toggle status.
     *
     * @param  \App\Models\User  $user  The user instance whose status will be toggled.
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);

        $this->userService->toggleStatus($user);

        $messageKey = $user->is_active ? 'messages.user_activated' : 'messages.user_deactivated';

        return $this->redirectWithSuccess('users.index', __($messageKey));
    }

    /**
     * Restore a soft-deleted user.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        try {
            $user = $this->userService->restoreUser($id);

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
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();

            if (! $this->userService->canForceDeleteUser($currentUser, $id)) {
                return $this->flashError(__('messages.unauthorized'));
            }

            $this->userService->forceDeleteUser($id);

            return $this->redirectWithSuccess('users.index', __('messages.user_deleted'));
        } catch (\Exception $e) {
            Log::error('Error permanently deleting user', ['user_id' => $id, 'error' => $e->getMessage()]);

            return $this->flashError(__('messages.operation_failed'));
        }
    }
}
