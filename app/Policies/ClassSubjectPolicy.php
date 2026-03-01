<?php

namespace App\Policies;

use App\Models\ClassSubject;
use App\Models\User;

/**
 * Policy for controlling access to ClassSubject operations.
 */
class ClassSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view class subjects');
    }

    public function view(User $user, ClassSubject $classSubject): bool
    {
        return $user->can('view class subjects');
    }

    public function create(User $user): bool
    {
        return $user->can('create class subjects');
    }

    public function update(User $user, ClassSubject $classSubject): bool
    {
        return $user->can('update class subjects');
    }

    public function delete(User $user, ClassSubject $classSubject): bool
    {
        return $user->can('delete class subjects');
    }

    public function replaceTeacher(User $user, ClassSubject $classSubject): bool
    {
        return $user->can('replace teacher class subjects');
    }
}
