<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Level;
use Illuminate\Auth\Access\Response;

/**
 * LevelPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class LevelPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des niveaux.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view levels');
    }

    /**
     * Détermine si l'utilisateur peut voir un niveau spécifique.
     */
    public function view(User $user, Level $level): bool
    {
        return $user->can('view levels');
    }

    /**
     * Détermine si l'utilisateur peut créer des niveaux.
     */
    public function create(User $user): bool
    {
        return $user->can('create levels');
    }

    /**
     * Détermine si l'utilisateur peut modifier un niveau.
     */
    public function update(User $user, Level $level): bool
    {
        return $user->can('update levels');
    }

    /**
     * Détermine si l'utilisateur peut supprimer un niveau.
     */
    public function delete(User $user, Level $level): bool
    {
        return $user->can('delete levels');
    }

    /**
     * Détermine si l'utilisateur peut gérer les niveaux.
     */
    public function manage(User $user): bool
    {
        return $user->can('manage levels');
    }

    /**
     * Détermine si l'utilisateur peut activer/désactiver un niveau.
     */
    public function toggleStatus(User $user, Level $level): bool
    {
        return $user->can('manage levels');
    }
}
