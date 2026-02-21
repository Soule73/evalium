<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserManagementServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\EditUserRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests, HandlesIndexRequests;

    public function __construct(
        public readonly UserManagementServiceInterface $userService,
        private readonly UserRepositoryInterface $userQueryService
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

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'role', 'status', 'include_deleted']
        );

        $filters['exclude_roles'] = ['student', 'teacher'];

        if (! $currentUser->hasRole('super_admin')) {
            $filters['exclude_roles'] = array_merge($filters['exclude_roles'], ['admin', 'super_admin']);
        }

        $users = $this->userQueryService->getUserWithPagination($filters, $perPage, $currentUser);

        $availableRoles = $this->userQueryService->getAvailableRoles($currentUser);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $availableRoles,
            'canManageAdmins' => $currentUser->hasRole('super_admin'),
            'canDeleteUsers' => $currentUser->can('delete users'),
            'adminCount' => User::role('admin')->count(),
            'superAdminCount' => User::role('super_admin')->count(),
        ]);
    }

    /**
     * Display the specified admin user profile.
     *
     * @param  \App\Models\User  $user  The user instance to display.
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        if ($this->userService->isTeacher($user) || $user->hasRole('student')) {
            return back()->flashError(__('messages.unauthorized'));
        }

        $this->userService->ensureRolesLoaded($user);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Admin/Users/ShowAdmin', [
            'user' => $user,
            'canDelete' => $currentUser->hasRole('super_admin'),
            'canToggleStatus' => $currentUser->can('update users'),
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

            ['user' => $user, 'password' => $password] = $this->userService->store($validated);

            session()->put('new_user_credentials', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ]);
            session()->flash('has_new_user', true);

            return back()->flashSuccess(__('messages.user_created'));
        } catch (\Exception $e) {
            Log::error('Error creating user', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Return and clear the pending new user credentials stored in session.
     *
     * The password is never exposed via Inertia shared props; it is fetched
     * once via this authenticated endpoint and then removed from the session.
     */
    public function pendingCredentials(Request $request): JsonResponse
    {
        $credentials = $request->session()->pull('new_user_credentials');

        if (! $credentials) {
            return response()->json(null, 404);
        }

        return response()->json($credentials);
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

            return back()->flashSuccess(__('messages.user_updated'));
        } catch (\Exception $e) {
            Log::error('Error updating user', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
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

        return redirect()->route('admin.users.index')->flashSuccess(__('messages.user_deleted'));
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

        return redirect()->route('admin.users.index')->flashSuccess(__($messageKey));
    }

    /**
     * Restore a soft-deleted user.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('restore', $user);

        try {
            $this->userService->restoreUser($id);

            return redirect()->route('admin.users.index')->flashSuccess(__('messages.user_restored'));
        } catch (\Exception $e) {
            Log::error('Error restoring user', ['user_id' => $id, 'error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Permanently delete a user (force delete).
     *
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $user);

        try {
            $this->userService->forceDeleteUser($id);

            return redirect()->route('admin.users.index')->flashSuccess(__('messages.user_deleted'));
        } catch (\Exception $e) {
            Log::error('Error permanently deleting user', ['user_id' => $id, 'error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }
}
