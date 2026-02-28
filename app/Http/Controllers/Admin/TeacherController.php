<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AdminAssessmentRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserManagementServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    use AuthorizesRequests, HandlesIndexRequests;

    public function __construct(
        private readonly UserManagementServiceInterface $userService,
        private readonly UserRepositoryInterface $userQueryService,
        private readonly AdminAssessmentRepositoryInterface $assessmentQueryService
    ) {}

    /**
     * Display a paginated list of teachers.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'status', 'include_deleted']
        );

        $filters['include_roles'] = ['teacher'];

        $teachers = $this->userQueryService->getUserWithPagination($filters, $perPage, $currentUser);

        $teacherCounts = User::role('teacher')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND deleted_at IS NULL THEN 1 ELSE 0 END) as active_count')
            ->selectRaw('SUM(CASE WHEN is_active = 0 AND deleted_at IS NULL THEN 1 ELSE 0 END) as inactive_count')
            ->first();

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => $teachers,
            'activeCount' => (int) ($teacherCounts->active_count ?? 0),
            'inactiveCount' => (int) ($teacherCounts->inactive_count ?? 0),
        ]);
    }

    /**
     * Display a teacher's profile with assessment statistics.
     */
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        $this->authorize('view', $user);

        if (! $this->userService->isTeacher($user)) {
            return back()->flashError(__('messages.unauthorized'));
        }

        $this->userService->ensureRolesLoaded($user);

        $isTeaching = $this->userService->isTeachingInCurrentYear($user);

        $filters = $request->only(['search', 'type', 'delivery_mode']);
        $filters['page'] = $request->input('page', 1);
        $perPage = $this->getPerPageFromRequest($request);

        $assessments = $this->assessmentQueryService->getAssessmentsForTeacher(
            $user,
            $filters,
            $perPage
        );

        $stats = $this->assessmentQueryService->getTeacherAssessmentStats($user);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Admin/Teachers/Show', [
            'user' => $user,
            'assessments' => $assessments,
            'stats' => $stats,
            'canDelete' => $currentUser->hasRole('super_admin') && ! $isTeaching,
            'canToggleStatus' => $currentUser->can('update users') && ! $isTeaching,
        ]);
    }

    /**
     * Toggle the active status of a teacher account.
     *
     * Blocked if the teacher is assigned to classes in the current academic year.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('toggleStatus', $user);

        if (! $this->userService->isTeacher($user)) {
            return back()->flashError(__('messages.unauthorized'));
        }

        if ($this->userService->isTeachingInCurrentYear($user)) {
            return back()->flashError(__('messages.teacher_has_active_assignments'));
        }

        $this->userService->toggleStatus($user);

        $messageKey = $user->is_active ? 'messages.user_activated' : 'messages.user_deactivated';

        return back()->flashSuccess(__($messageKey));
    }

    /**
     * Remove the specified teacher from storage.
     *
     * Only super_admin can delete. Blocked if teacher has active class assignments in current year.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser->hasRole('super_admin')) {
            return back()->flashError(__('messages.unauthorized'));
        }

        if (! $this->userService->isTeacher($user)) {
            return back()->flashError(__('messages.unauthorized'));
        }

        if ($this->userService->isTeachingInCurrentYear($user)) {
            return back()->flashError(__('messages.teacher_has_active_assignments'));
        }

        $this->userService->delete($user);

        return redirect()->route('admin.teachers.index')->flashSuccess(__('messages.user_deleted'));
    }

    /**
     * Store a newly created teacher in storage.
     *
     * Forces the role to 'teacher' regardless of request input.
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        try {
            $validated = $request->validated();
            $validated['role'] = 'teacher';

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
            Log::error('Error creating teacher', ['error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }
}
