<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Answer;
use Illuminate\Auth\Access\Response;

/**
 * AnswerPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class AnswerPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des réponses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view answers');
    }

    /**
     * Détermine si l'utilisateur peut voir une réponse spécifique.
     */
    public function view(User $user, Answer $answer): bool
    {
        // STRATÉGIE HYBRIDE : Un étudiant peut SEULEMENT voir ses propres réponses
        if ($user->hasRole('student')) {
            return $answer->assignment && $answer->assignment->student_id === $user->id;
        }

        // Autres utilisateurs : basé sur la permission
        return $user->can('view answers');
    }

    /**
     * Détermine si l'utilisateur peut créer des réponses.
     */
    public function create(User $user): bool
    {
        // STRATÉGIE HYBRIDE : Les étudiants peuvent créer des réponses
        // (vérifié dans le controller qu'il s'agit de leurs propres réponses)
        if ($user->hasRole('student')) {
            return true;
        }

        // Autres utilisateurs : basé sur la permission
        return $user->can('create answers');
    }

    /**
     * Détermine si l'utilisateur peut modifier une réponse.
     */
    public function update(User $user, Answer $answer): bool
    {
        // STRATÉGIE HYBRIDE : Un étudiant peut SEULEMENT modifier ses propres réponses
        // et SEULEMENT avant la soumission
        if ($user->hasRole('student')) {
            return $answer->assignment &&
                $answer->assignment->student_id === $user->id &&
                $answer->assignment->status !== 'submitted';
        }

        // Autres utilisateurs : basé sur la permission
        return $user->can('update answers');
    }

    /**
     * Détermine si l'utilisateur peut supprimer une réponse.
     */
    public function delete(User $user, Answer $answer): bool
    {
        return $user->can('delete answers');
    }

    /**
     * Détermine si l'utilisateur peut noter une réponse.
     */
    public function grade(User $user, Answer $answer): bool
    {
        return $user->can('grade answers');
    }
}
