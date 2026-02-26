<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

/**
 * Defines authorization rules for the Assessment resource.
 *
 * Handles view, create, update, delete and publish permissions
 * for admin, teacher and student roles.
 */
class AssessmentPolicy
{
    /**
     * Determine if the user has an admin-level role (admin or super_admin).
     */
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view any assessments.
     * Teachers can view assessments for their assigned class-subjects.
     * Admins can view all assessments.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasRole('teacher');
    }

    /**
     * Determine whether the user can view the assessment.
     * Teachers can only view assessments they created.
     * Students can view assessments for their enrolled class.
     * Admins can view all assessments.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $assessment->classSubject?->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $user->enrollments()
                ->where('status', 'active')
                ->whereHas('class.classSubjects', function ($query) use ($assessment) {
                    $query->where('id', $assessment->class_subject_id);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create assessments.
     * Only teachers and admins can create assessments.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasRole('teacher');
    }

    /**
     * Determine whether the user can update the assessment.
     * Teachers can only update their own assessments.
     * Admins can update all assessments.
     */
    public function update(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $assessment->classSubject?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the assessment.
     * Teachers can only delete their own assessments.
     * Admins can delete all assessments.
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $assessment->classSubject?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the assessment.
     */
    public function restore(User $user, Assessment $assessment): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the assessment.
     */
    public function forceDelete(User $user, Assessment $assessment): bool
    {
        return $this->isAdmin($user);
    }
}
