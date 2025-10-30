<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExamAssignment;
use Illuminate\Auth\Access\Response;

/**
 * ExamAssignmentPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class ExamAssignmentPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des assignations.
     */
    public function viewAny(User $user): bool
    {
        // STRATÉGIE HYBRIDE : Les étudiants peuvent voir leurs assignations
        if ($user->hasRole('student')) {
            return true;
        }

        // Autres utilisateurs : basé sur la permission
        return $user->can('view assignments');
    }

    /**
     * Détermine si l'utilisateur peut voir une assignation spécifique.
     */
    public function view(User $user, ExamAssignment $assignment): bool
    {
        // STRATÉGIE HYBRIDE : Un étudiant peut SEULEMENT voir ses propres assignations
        if ($user->hasRole('student')) {
            return $assignment->student_id === $user->id;
        }

        // Autres utilisateurs : basé sur la permission
        return $user->can('view assignments');
    }

    /**
     * Détermine si l'utilisateur peut créer des assignations.
     */
    public function create(User $user): bool
    {
        return $user->can('create assignments');
    }

    /**
     * Détermine si l'utilisateur peut modifier une assignation.
     */
    public function update(User $user, ExamAssignment $assignment): bool
    {
        return $user->can('update assignments');
    }

    /**
     * Détermine si l'utilisateur peut supprimer une assignation.
     */
    public function delete(User $user, ExamAssignment $assignment): bool
    {
        return $user->can('delete assignments');
    }

    /**
     * Détermine si l'utilisateur peut soumettre une assignation.
     */
    public function submit(User $user, ExamAssignment $assignment): bool
    {
        // STRICT : Seulement les étudiants peuvent soumettre
        if (!$user->hasRole('student')) {
            return false;
        }

        // Un étudiant peut SEULEMENT soumettre sa propre assignation
        return $assignment->student_id === $user->id &&
            $assignment->status === 'in_progress';
    }

    /**
     * Détermine si l'utilisateur peut noter une assignation.
     */
    public function grade(User $user, ExamAssignment $assignment): bool
    {
        return $user->can('grade assignments');
    }
}
