<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;
use Illuminate\Auth\Access\Response;

/**
 * GroupPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class GroupPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des groupes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view groups');
    }

    /**
     * Détermine si l'utilisateur peut voir un groupe spécifique.
     */
    public function view(User $user, Group $group): bool
    {
        return $user->can('view groups');
    }

    /**
     * Détermine si l'utilisateur peut créer des groupes.
     */
    public function create(User $user): bool
    {
        return $user->can('create groups');
    }

    /**
     * Détermine si l'utilisateur peut modifier un groupe.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->can('update groups');
    }

    /**
     * Détermine si l'utilisateur peut supprimer un groupe.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->can('delete groups');
    }

    /**
     * Détermine si l'utilisateur peut gérer les étudiants d'un groupe.
     */
    public function manageStudents(User $user, Group $group): bool
    {
        return $user->can('manage group students');
    }

    /**
     * Détermine si l'utilisateur peut assigner des examens à un groupe.
     */
    public function assignExams(User $user, Group $group): bool
    {
        return $user->can('assign group exams');
    }

    /**
     * Détermine si l'utilisateur peut activer/désactiver un groupe.
     */
    public function toggleStatus(User $user, Group $group): bool
    {
        return $user->can('toggle group status');
    }
}
