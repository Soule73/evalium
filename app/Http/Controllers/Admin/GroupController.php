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

class GroupController extends Controller
{
    use HasFlashMessages;

    public function __construct(
        private readonly GroupService $groupService
    ) {}

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

    public function create(): Response
    {
        $formData = $this->groupService->getFormData();
        return Inertia::render('Admin/Groups/Create', $formData);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        try {
            $this->groupService->createGroup($request->validated());
            return $this->redirectWithSuccess('groups.index', 'Groupe créé avec succès.');
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de la création du groupe.');
        }
    }

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

    public function edit(Group $group): Response
    {
        $group->load('level');
        $formData = $this->groupService->getFormData();
        return Inertia::render('Admin/Groups/Edit', array_merge($formData, [
            'group' => $group,
        ]));
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupService->updateGroup($group, $request->validated());
            return $this->redirectWithSuccess('groups.show', 'Groupe mis à jour avec succès.', ['group' => $group->id]);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de la mise à jour du groupe.');
        }
    }

    public function destroy(Group $group): RedirectResponse
    {
        try {
            $this->groupService->deleteGroup($group);
            return $this->redirectWithSuccess('groups.index', 'Groupe supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de la suppression du groupe.');
        }
    }

    public function assignStudents(Group $group): Response
    {
        $availableStudents = $this->groupService->getAvailableStudents();
        return Inertia::render('Admin/Groups/AssignStudents', [
            'group' => $group,
            'availableStudents' => $availableStudents,
        ]);
    }

    public function storeStudents(AssignStudentsToGroupRequest $request, Group $group): RedirectResponse
    {
        try {
            $result = $this->groupService->assignStudentsToGroup($group, $request->validated()['student_ids']);

            $message = "Assignation terminée : {$result['assigned_count']} étudiants assignés";
            if ($result['already_assigned_count'] > 0) {
                $message .= " ({$result['already_assigned_count']} déjà assignés)";
            }

            return $this->redirectWithSuccess('groups.show', $message, ['group' => $group->id]);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de l\'assignation des étudiants.');
        }
    }

    public function removeStudent(Group $group, User $student): RedirectResponse
    {
        try {
            $this->groupService->removeStudentFromGroup($group, $student);
            return $this->redirectWithSuccess('groups.show', 'Étudiant retiré du groupe avec succès.', ['group' => $group->id]);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors du retrait de l\'étudiant.');
        }
    }

    public function bulkActivate(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:groups,id',
        ]);

        try {
            $result = $this->groupService->bulkActivate($request->input('ids'));

            $message = "{$result['activated_count']} groupe(s) activé(s) avec succès";
            if ($result['already_active_count'] > 0) {
                $message .= " ({$result['already_active_count']} déjà actif(s))";
            }

            return $this->redirectWithSuccess('groups.index', $message);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de l\'activation des groupes.');
        }
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:groups,id',
        ]);

        try {
            $result = $this->groupService->bulkDeactivate($request->input('ids'));

            $message = "{$result['deactivated_count']} groupe(s) désactivé(s) avec succès";
            if ($result['already_inactive_count'] > 0) {
                $message .= " ({$result['already_inactive_count']} déjà inactif(s))";
            }

            return $this->redirectWithSuccess('groups.index', $message);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors de la désactivation des groupes.');
        }
    }

    public function bulkRemoveStudents(Request $request, Group $group): RedirectResponse
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = $this->groupService->bulkRemoveStudentsFromGroup($group, $request->input('student_ids'));

            $message = "{$result['removed_count']} étudiant(s) retiré(s) avec succès";
            if ($result['not_in_group_count'] > 0) {
                $message .= " ({$result['not_in_group_count']} n'étaient pas dans le groupe)";
            }

            return $this->redirectWithSuccess('groups.show', $message, ['group' => $group->id]);
        } catch (\Exception $e) {
            return $this->flashError('Erreur lors du retrait des étudiants.');
        }
    }
}
