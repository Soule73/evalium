<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Group;
use App\Services\Admin\GroupService;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function __construct(
        private readonly GroupService $groupService
    ) {}

    public function getUserWithPagination(array $filters, int $perPage, User $currentUser)
    {
        $query = User::with('roles')->whereNot('id', $currentUser->id);

        // Exclure certains rôles si spécifié (admins ne peuvent pas voir d'autres admins)
        if (!empty($filters['exclude_roles'])) {
            $query->whereDoesntHave('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['exclude_roles']);
            });
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        // Filtrer par statut actif/inactif
        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // Inclure les utilisateurs supprimés si demandé
        if (!empty($filters['include_deleted'])) {
            $query->withTrashed();
        }

        $per_page = $filters['per_page'] ?? 10;

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($per_page)->withQueryString();

        return $users;
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Générer un mot de passe aléatoire
            $password = Str::random(12);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $user->assignRole($data['role']);

            // Si c'est un étudiant et qu'un groupe est spécifié
            if ($data['role'] === 'student' && isset($data['group_id'])) {
                $group = Group::findOrFail($data['group_id']);
                $this->groupService->assignStudentToGroup($group, $user->id);
            }

            // Envoyer l'email avec les credentials
            $user->notify(new UserCredentialsNotification($password, $data['role']));

            return $user;
        });
    }

    public function update(User $user, array $data)
    {
        try {
            DB::transaction(function () use ($user, $data) {
                $updatedData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];

                // Ne mettre à jour le mot de passe que s'il est fourni
                // (pour la modification, le mot de passe n'est pas requis)
                if (isset($data['password']) && $data['password']) {
                    $updatedData['password'] = Hash::make($data['password']);
                }

                $user->update($updatedData);

                if (!isset($data['role']) || !Role::where('name', $data['role'])->exists()) {
                    throw new \InvalidArgumentException("Le rôle est requis pour la mise à jour.");
                }

                $user->syncRoles([$data['role']]);

                // Si le rôle change vers student et qu'un groupe est fourni
                if ($data['role'] === 'student' && isset($data['group_id'])) {
                    $group = Group::findOrFail($data['group_id']);
                    $this->groupService->assignStudentToGroup($group, $user->id);
                }
            });
        } catch (\Exception $e) {
            Log::error(
                "Erreur lors de la mise à jour de l'utilisateur : " . $e->getMessage()
            );
            throw $e;
        }
    }

    public function delete(User $user)
    {
        // Soft delete - les relations seront conservées
        $user->delete();
    }

    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
    }

    public function changeStudentGroup(User $student, int $newGroupId)
    {
        if (!$student->hasRole('student')) {
            throw new \InvalidArgumentException("L'utilisateur doit être un étudiant.");
        }

        $newGroup = Group::findOrFail($newGroupId);
        $this->groupService->assignStudentToGroup($newGroup, $student->id);
    }
}
