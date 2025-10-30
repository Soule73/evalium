<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * UserPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class UserPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des utilisateurs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    /**
     * Détermine si l'utilisateur peut voir un utilisateur spécifique.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('view users') || $user->id === $model->id;
    }

    /**
     * Détermine si l'utilisateur peut créer des utilisateurs.
     */
    public function create(User $user): bool
    {
        return $user->can('create users');
    }

    /**
     * Détermine si l'utilisateur peut modifier un utilisateur.
     */
    public function update(User $user, User $model): bool
    {
        // Un utilisateur peut toujours modifier son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('update users');
    }

    /**
     * Détermine si l'utilisateur peut supprimer un utilisateur.
     */
    public function delete(User $user, User $model): bool
    {
        // Ne peut pas se supprimer soi-même
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('delete users');
    }

    /**
     * Détermine si l'utilisateur peut restaurer un utilisateur.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('restore users');
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement un utilisateur.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Ne peut pas se forceDelete soi-même
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('force delete users');
    }

    /**
     * Détermine si l'utilisateur peut gérer les étudiants.
     */
    public function manageStudents(User $user): bool
    {
        return $user->can('manage students');
    }

    /**
     * Détermine si l'utilisateur peut gérer les enseignants.
     */
    public function manageTeachers(User $user): bool
    {
        return $user->can('manage teachers');
    }

    /**
     * Détermine si l'utilisateur peut gérer les admins.
     */
    public function manageAdmins(User $user): bool
    {
        return $user->can('manage admins');
    }

    /**
     * Détermine si l'utilisateur peut activer/désactiver un utilisateur.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Ne peut pas changer son propre statut
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('toggle user status');
    }
}
