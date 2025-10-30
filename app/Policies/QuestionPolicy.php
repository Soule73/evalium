<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Question;
use Illuminate\Auth\Access\Response;

/**
 * QuestionPolicy - Contrôle d'accès basé sur les permissions Spatie
 */
class QuestionPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des questions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view questions');
    }

    /**
     * Détermine si l'utilisateur peut voir une question spécifique.
     */
    public function view(User $user, Question $question): bool
    {
        return $user->can('view questions');
    }

    /**
     * Détermine si l'utilisateur peut créer des questions.
     */
    public function create(User $user): bool
    {
        return $user->can('create questions');
    }

    /**
     * Détermine si l'utilisateur peut modifier une question.
     */
    public function update(User $user, Question $question): bool
    {
        return $user->can('update questions');
    }

    /**
     * Détermine si l'utilisateur peut supprimer une question.
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->can('delete questions');
    }
}
