<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Group;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Traits\HasFlashMessages;
use App\Http\Requests\Admin\EditUserRequest;
use App\Services\Admin\UserManagementService;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\ChangeStudentGroupRequest;
use App\Services\Core\ExamQueryService;

class UserManagementController extends Controller
{
    use HasFlashMessages;

    public function __construct(
        public readonly UserManagementService $userService,
        public readonly ExamQueryService $examQueryService
    ) {}

    /**
     * Display a listing of the users.
     * 
     * Delegates to UserManagementService to load paginated users with filtering.
     * Restricts admin visibility based on user role (super_admin can see all).
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $filters = $request->only(['search', 'role', 'per_page', 'status', 'include_deleted']);

        // Regular admins cannot see other admins
        if (!$currentUser->hasRole('super_admin')) {
            $filters['exclude_roles'] = ['admin', 'super_admin'];
        }

        $users = $this->userService->getUserWithPagination($filters, 10, $currentUser);

        // Filter roles based on permissions
        $availableRoles = $currentUser->hasRole('super_admin')
            ? Role::pluck('name')
            : Role::whereNotIn('name', ['admin', 'super_admin'])->pluck('name');


        $groups = Cache::remember('groups_active_with_levels', 3600, function () {
            return Group::active()->with('level')->orderBy('academic_year', 'desc')->get();
        });

        return Inertia::render('Admin/Users/Index', [
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
        try {
            $validated = $request->validated();

            $this->userService->store($validated);

            return $this->redirectWithSuccess('users.index', 'User created successfully.');
        } catch (\Exception $e) {
            return $this->flashError("Error creating user");
        }
    }

    /**
     * Display the specified teacher's details with exams.
     * 
     * Delegates to ExamQueryService to load paginated exams for teacher.
     * Uses eager loading for optimization.
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     * @param \App\Models\User $user The user instance representing the teacher.
     * @return \Illuminate\Http\Response
     */
    public function showTeacher(Request $request, User $user)
    {
        if (!$user->hasRole('teacher')) {
            return $this->flashError("User is not a teacher.");
        }

        $perPage = $request->input('per_page', 10);
        $status = null;

        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status') === '1' ? true : false;
        }

        $search = $request->input('search');

        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        $exams = $this->examQueryService->getExamsForTeacher($user->id, $perPage, $status, $search);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Admin/Users/ShowTeacher', [
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
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @param \App\Models\User $user The user instance representing the student.
     * @return \Illuminate\Http\Response
     */
    public function showStudent(Request $request, User $user)
    {
        if (!$user->hasRole('student')) {
            return $this->flashError("User is not a student.");
        }

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status') ? $request->input('status') : null;
        $search = $request->input('search');

        // Load ALL groups (active and inactive) with their active exams once with eager loading
        // Active filtering is handled in ExamQueryService
        $user->load([
            'groups' => function ($query) {
                $query->with([
                    'level',
                    'exams' => function ($q) {
                        $q->where('is_active', true);
                    }
                ])
                    ->withPivot(['enrolled_at', 'left_at', 'is_active'])
                    ->orderBy('group_student.enrolled_at', 'desc');
            }
        ]);

        $assignments = $this->examQueryService->getAssignedExamsForStudent($user, $perPage, $status, $search);

        $availableGroups = Cache::remember('groups_active_with_levels', 3600, function () {
            return Group::active()->with('level')->orderBy('academic_year', 'desc')->get();
        });

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Admin/Users/ShowStudent', [
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
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Unauthorized. Only super admin can edit administrators.");
        }

        try {
            $validated = $request->validated();


            $this->userService->update($user, $validated);

            return $this->flashSuccess('User updated successfully.');
        } catch (\Exception $e) {

            return $this->flashError("Error updating user");
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
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError('You cannot delete your own account.');
        }

        // Only super_admin can delete accounts
        if (!$auth->can('delete users')) {
            return $this->flashError("Unauthorized. You do not have permission to delete users.");
        }

        // Only super_admin can delete admins
        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Unauthorized. Only super admin can delete administrators.");
        }

        $this->userService->delete($user);

        return $this->redirectWithSuccess('users.index', 'User deleted successfully.');
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
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError('You cannot modify your own account status.');
        }

        if (!$auth->can('toggle user status')) {
            return $this->flashError("Unauthorized. You do not have permission to modify user status.");
        }

        // Only super_admin can modify admin status
        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Unauthorized. Only super admin can modify administrator status.");
        }

        $this->userService->toggleStatus($user);

        return $this->redirectWithSuccess('users.index', 'User status modified.');
    }

    /**
     * Change the group of a student.
     * 
     * Delegates to UserManagementService to reassign student to new group.
     *
     * @param ChangeStudentGroupRequest $request
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function changeStudentGroup(ChangeStudentGroupRequest $request, User $user)
    {
        if (!$user->hasRole('student')) {
            return $this->flashError("User is not a student.");
        }

        try {
            $this->userService->changeStudentGroup($user, $request->validated()['group_id']);
            return $this->redirectWithSuccess('users.show.student', 'Student group changed successfully.', ['user' => $user->id]);
        } catch (\Exception $e) {
            return $this->flashError("Error changing group: " . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if (!$auth->can('restore users')) {
            return $this->flashError("Unauthorized. You do not have permission to restore users.");
        }

        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return $this->redirectWithSuccess('users.index', 'User restored successfully.');
        } catch (\Exception $e) {
            return $this->flashError("Error restoring user.");
        }
    }

    /**
     * Permanently delete a user (force delete).
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(int $id)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if (!$auth->can('force delete users')) {
            return $this->flashError("Unauthorized. You do not have permission to permanently delete users.");
        }

        try {
            $user = User::withTrashed()->findOrFail($id);

            if ($user->id === $auth->id) {
                return $this->flashError('You cannot delete your own account.');
            }

            $user->forceDelete();

            return $this->redirectWithSuccess('users.index', 'User permanently deleted.');
        } catch (\Exception $e) {
            return $this->flashError("Error permanently deleting user.");
        }
    }
}
