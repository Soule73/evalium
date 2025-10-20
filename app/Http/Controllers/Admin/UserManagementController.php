<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Group;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HasFlashMessages;
use App\Http\Requests\Admin\EditUserRequest;
use App\Services\Admin\UserManagementService;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Services\ExamService;

class UserManagementController extends Controller
{
    use HasFlashMessages;

    public function __construct(
        public readonly UserManagementService $userService,
        public readonly ExamService $examService
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
        $filters = $request->only(['search', 'role', 'per_page']);

        $users = $this->userService->getUserWithPagination($filters, 10, Auth::user());

        $roles = Role::pluck('name');
        $groups = Group::active()->with('level')->orderBy('academic_year', 'desc')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'groups' => $groups,
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

            return $this->redirectWithSuccess('admin.users.index', 'Utilisateur créé avec succès.');
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors de la création de l'utilisateur ");
        }
    }

    /**
     * Display the specified teacher's details.
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

        $user->load('roles');

        $exams = $this->examService->getTeacherExams($user->id, $perPage, $status, $search);


        return Inertia::render('Admin/Users/ShowTeacher', [
            'user' => $user,
            'exams' => $exams,
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

        $perPage = $request->input('per_page', 10);

        $user->load(['roles', 'groups' => function ($query) {
            $query->withPivot(['enrolled_at', 'left_at', 'is_active'])
                ->orderBy('group_student.enrolled_at', 'desc');
        }]);

        $assignments = $this->examService->getAssignedExamsForStudent($user, $perPage, $status, $search);

        $availableGroups = Group::active()->with('level')->orderBy('academic_year', 'desc')->get();

        return Inertia::render('Admin/Users/ShowStudent', [
            'user' => $user,
            'examsAssignments' => $assignments,
            'availableGroups' => $availableGroups,
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
        $auth = Auth::user();
        if ($user->id === $auth->id()) {
            return $this->flashError('Vous ne pouvez pas supprimer votre propre compte.');
        }

        if (!$auth->hasRole('admin')) {
            return $this->flashError("Non autorisé.Vous n'êtes pas administrateur.");
        }

        $this->userService->delete($user);

        return $this->redirectWithSuccess('admin.users.index', 'Utilisateur supprimé avec succès.');
    }


    /**
     * Toggle the status (active/inactive) of the specified user.
     *
     * @param  \App\Models\User  $user  The user instance whose status will be toggled.
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(User $user)
    {
        $auth = Auth::user();
        if ($user->id === $auth->id()) {
            return $this->flashError('Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        if (!$auth->hasRole('admin')) {
            return $this->flashError("Non autorisé.Vous n'êtes pas administrateur.");
        }

        $this->userService->toggleStatus($user);



        return $this->redirectWithSuccess('admin.users.index', 'Statut de l\'utilisateur modifié.');
    }

    /**
     * Change le groupe d'un étudiant
     */
    public function changeStudentGroup(Request $request, User $user)
    {
        $request->validate([
            'group_id' => 'required|integer|exists:groups,id'
        ]);

        if (!$user->hasRole('student')) {
            return $this->flashError("L'utilisateur n'est pas un étudiant.");
        }

        try {
            $this->userService->changeStudentGroup($user, $request->group_id);
            return $this->redirectWithSuccess('admin.users.index', 'Groupe de l\'étudiant modifié avec succès.');
        } catch (\Exception $e) {
            return $this->flashError("Erreur lors du changement de groupe : " . $e->getMessage());
        }
    }
}
