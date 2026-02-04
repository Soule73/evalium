<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    /**
     * Determine whether the user can view any assessments.
     * Teachers can view assessments for their assigned class-subjects.
     * Admins can view all assessments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('teacher');
    }

    /**
     * Determine whether the user can view the assessment.
     * Teachers can only view assessments they created.
     * Students can view assessments for their enrolled class.
     * Admins can view all assessments.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->hasRole('admin')) {
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
        return $user->hasRole('admin') || $user->hasRole('teacher');
    }

    /**
     * Determine whether the user can update the assessment.
     * Teachers can only update their own assessments.
     * Admins can update all assessments.
     */
    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->hasRole('admin')) {
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
        if ($user->hasRole('admin')) {
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
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the assessment.
     */
    public function forceDelete(User $user, Assessment $assessment): bool
    {
        return $user->hasRole('admin');
    }
}
