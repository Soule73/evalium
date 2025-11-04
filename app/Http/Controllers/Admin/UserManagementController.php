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
     * Handles the incoming request to retrieve and display a list of users for management purposes.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $filters = $request->only(['search', 'role', 'per_page', 'status', 'include_deleted']);

        // Les admins normaux ne peuvent pas voir les autres admins
        if (!$currentUser->hasRole('super_admin')) {
            $filters['exclude_roles'] = ['admin', 'super_admin'];
        }

        $users = $this->userService->getUserWithPagination($filters, 10, $currentUser);

        // Filtrer les rôles selon les permissions
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
     * Handles the incoming request to create a new user using the validated data
     * from the CreateUserRequest. Performs necessary business logic and persists
     * the user to the database.
     *
     * @param  \App\Http\Requests\CreateUserRequest  $request  The validated request containing user data.
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->userService->store($validated);

            return $this->redirectWithSuccess('users.index', 'Utilisateur créé avec succès.');
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors de la création de l'utilisateur ");
        }
    }

    /**
     * Display the specified teacher's details.
     * Optimisé avec eager loading des relations
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     * @param \App\Models\User $user The user instance representing the teacher.
     * @return \Illuminate\Http\Response
     */
    public function showTeacher(Request $request, User $user)
    {
        if (!$user->hasRole('teacher')) {
            return $this->flashError("L'utilisateur n'est pas un professeur.");
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
     * Display the specified student details.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @param \App\Models\User $user The user instance representing the student.
     * @return \Illuminate\Http\Response
     */
    public function showStudent(Request $request, User $user)
    {
        if (!$user->hasRole('student')) {
            return $this->flashError("L'utilisateur n'est pas un étudiant.");
        }

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status') ? $request->input('status') : null;
        $search = $request->input('search');

        // Charger les relations une seule fois avec eager loading optimisé
        // Charger TOUS les groupes (actifs et inactifs) avec leurs exams actifs
        // Le filtre sur is_active se fera dans ExamQueryService
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
     * @param  \App\Http\Requests\EditUserRequest  $request  The validated request containing user update data.
     * @param  \App\Models\User  $user  The user instance to be updated.
     * @return \Illuminate\Http\Response
     */
    public function update(EditUserRequest $request, User $user)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Non autorisé. Seul le super administrateur peut modifier des administrateurs.");
        }

        try {
            $validated = $request->validated();


            $this->userService->update($user, $validated);

            return $this->flashSuccess('Utilisateur mis à jour avec succès.');
        } catch (\Exception $e) {

            return $this->flashError("Erreur lors de la mise à jour de l'utilisateur ");
        }
    }


    /**
     * Remove the specified user from storage.
     *
     * Handles the incoming request to delete a user. Ensures that the user
     * cannot delete their own account and performs necessary business logic
     * to safely remove the user from the database.
     *
     * @param  \App\Models\User  $user  The user instance to be deleted.
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError('Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Seul le super_admin peut supprimer des comptes
        if (!$auth->can('delete users')) {
            return $this->flashError("Non autorisé. Vous n'avez pas la permission de supprimer des utilisateurs.");
        }

        // Seul le super_admin peut supprimer des admins
        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Non autorisé. Seul le super administrateur peut supprimer des administrateurs.");
        }

        $this->userService->delete($user);

        return $this->redirectWithSuccess('users.index', 'Utilisateur supprimé avec succès.');
    }


    /**
     * Toggle the status (active/inactive) of the specified user.
     *
     * @param  \App\Models\User  $user  The user instance whose status will be toggled.
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(User $user)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if ($user->id === $auth->id) {
            return $this->flashError('Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        if (!$auth->can('toggle user status')) {
            return $this->flashError("Non autorisé. Vous n'avez pas la permission de modifier le statut des utilisateurs.");
        }

        // Seul le super_admin peut modifier le statut des admins
        if ($user->hasRole(['admin', 'super_admin']) && !$auth->hasRole('super_admin')) {
            return $this->flashError("Non autorisé. Seul le super administrateur peut modifier le statut des administrateurs.");
        }

        $this->userService->toggleStatus($user);

        return $this->redirectWithSuccess('users.index', 'Statut de l\'utilisateur modifié.');
    }

    /**
     * Change le groupe d'un étudiant
     */
    public function changeStudentGroup(ChangeStudentGroupRequest $request, User $user)
    {
        if (!$user->hasRole('student')) {
            return $this->flashError("L'utilisateur n'est pas un étudiant.");
        }

        try {
            $this->userService->changeStudentGroup($user, $request->validated()['group_id']);
            return $this->redirectWithSuccess('users.show.student', 'Groupe de l\'étudiant modifié avec succès.', ['user' => $user->id]);
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors du changement de groupe : " . $e->getMessage());
        }
    }

    /**
     * Restaurer un utilisateur supprimé (soft delete)
     */
    public function restore(int $id)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if (!$auth->can('restore users')) {
            return $this->flashError("Non autorisé. Vous n'avez pas la permission de restaurer des utilisateurs.");
        }

        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return $this->redirectWithSuccess('users.index', 'Utilisateur restauré avec succès.');
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors de la restauration de l'utilisateur.");
        }
    }

    /**
     * Supprimer définitivement un utilisateur
     */
    public function forceDelete(int $id)
    {
        /** @var \App\Models\User $auth */
        $auth = Auth::user();

        if (!$auth->can('force delete users')) {
            return $this->flashError("Non autorisé. Vous n'avez pas la permission de supprimer définitivement des utilisateurs.");
        }

        try {
            $user = User::withTrashed()->findOrFail($id);

            if ($user->id === $auth->id) {
                return $this->flashError('Vous ne pouvez pas supprimer votre propre compte.');
            }

            $user->forceDelete();

            return $this->redirectWithSuccess('users.index', 'Utilisateur supprimé définitivement.');
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors de la suppression définitive de l'utilisateur.");
        }
    }
}
