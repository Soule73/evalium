<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view academic years');
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('view academic years');
    }

    public function create(User $user): bool
    {
        return $user->can('create academic years');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('update academic years');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('delete academic years');
    }

    public function archive(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('archive academic years');
    }
}
