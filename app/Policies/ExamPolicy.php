<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * ExamPolicy - Utilise maintenant les PERMISSIONS au lieu des ROLES
 * 
 * Cela permet la création de rôles personnalisés par les administrateurs
 * tout en maintenant un contrôle d'accès cohérent basé sur les permissions.
 */
class ExamPolicy
{
    /**
     * Détermine si l'utilisateur peut voir tous les examens.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any exams') || $user->can('create exams');
    }

    /**
     * Détermine si l'utilisateur peut voir un examen spécifique.
     */
    public function view(User $user, Exam $exam): bool
    {
        // STRATÉGIE HYBRIDE : Les étudiants voient SEULEMENT les examens qui leur sont assignés
        if ($user->hasRole('student')) {
            return $exam->assignments()
                ->where('student_id', $user->id)
                ->exists();
        }

        // Autres utilisateurs : permission 'view any exams' OU propriétaire
        return $user->can('view any exams') || $exam->teacher_id === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut créer des examens.
     */
    public function create(User $user): bool
    {
        return $user->can('create exams');
    }

    /**
     * Détermine si l'utilisateur peut modifier un examen.
     */
    public function update(User $user, Exam $exam): bool
    {
        return $user->can('update exams') &&
            ($user->can('view any exams') || $exam->teacher_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer un examen.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->can('delete exams') &&
            ($user->can('view any exams') || $exam->teacher_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut restaurer un examen.
     */
    public function restore(User $user, Exam $exam): bool
    {
        return $user->can('restore exams') &&
            ($user->can('view any exams') || $exam->teacher_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement un examen.
     */
    public function forceDelete(User $user, Exam $exam): bool
    {
        return $user->can('force delete exams');
    }

    /**
     * Détermine si l'utilisateur peut assigner un examen.
     */
    public function assign(User $user, Exam $exam): bool
    {
        return $user->can('assign exams') &&
            ($user->can('view any exams') || $exam->teacher_id === $user->id);
    }

    /**
     * Détermine si l'utilisateur peut PASSER un examen (student-only).
     * Cette méthode est STRICTEMENT réservée au rôle "student".
     */
    public function take(User $user, Exam $exam): bool
    {
        // STRICT : Seulement les étudiants peuvent passer un examen
        if (!$user->hasRole('student')) {
            return false;
        }

        // Vérifier que l'examen est assigné à l'étudiant
        $assignment = $exam->assignments()
            ->where('student_id', $user->id)
            ->first();

        if (!$assignment) {
            return false;
        }

        // Vérifier que l'examen n'est pas déjà commencé ou soumis
        return in_array($assignment->status, ['assigned', 'not_started']);
    }

    /**
     * Détermine si l'utilisateur peut SOUMETTRE un examen (student-only).
     */
    public function submit(User $user, Exam $exam): bool
    {
        // STRICT : Seulement les étudiants
        if (!$user->hasRole('student')) {
            return false;
        }

        $assignment = $exam->assignments()
            ->where('student_id', $user->id)
            ->first();

        return $assignment && $assignment->status === 'in_progress';
    }
}
