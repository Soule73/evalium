<?php

namespace App\Policies;

use App\Models\ClassModel;
use App\Models\User;

class ClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view classes');
    }

    public function view(User $user, ClassModel $class): bool
    {
        return $user->can('view classes');
    }

    public function create(User $user): bool
    {
        return $user->can('create classes');
    }

    public function update(User $user, ClassModel $class): bool
    {
        return $user->can('update classes');
    }

    public function delete(User $user, ClassModel $class): bool
    {
        return $user->can('delete classes');
    }
}
