<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

// use Illuminate\Auth\Access\Response;

/**
 * ExamPolicy - Policies for managing access to Exam model.
 *
 * This policy class defines authorization logic for various actions
 * related to the Exam model, such as viewing, creating, updating,
 * deleting, restoring, and force deleting exams.
 */
class ExamPolicy
{
    /**
     * Determine whether the given user is allowed to view any exams.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view any exams,
     * - whether the user has permission to create exams,
     *
     * @param  User  $user  The user attempting to view exams.
     * @return bool True if the user is authorized to view any exams, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any exams') || $user->can('create exams');
    }

    /**
     * Determine whether the given user is allowed to view the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to view any exams,
     * - whether the user owns the exam,
     * - whether the user is enrolled in the exam (for students),
     *
     * @param  User  $user  The user attempting to view the exam.
     * @param  Exam  $exam  The exam instance to be viewed.
     * @return bool True if the user is authorized to view the exam, false otherwise.
     */
    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasRole('student')) {
            return $exam->assignments()->where('student_id', $user->id)->exists()
                || $exam->groups()->whereIn('groups.id', $user->activeGroups()->pluck('groups.id'))->exists();
        }

        return $user->can('view any exams') || $exam->teacher_id === $user->id;
    }

    /**
     * Determine whether the given user is allowed to create exams.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to create exams,
     *
     * @param  User  $user  The user attempting to create an exam.
     * @return bool True if the user is authorized to create exams, false otherwise.
     */
    public function create(User $user): bool
    {
        return $user->can('create exams');
    }

    /**
     * Determine whether the given user is allowed to update the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to update exams,
     *
     * @param  User  $user  The user attempting to update the exam.
     * @param  Exam  $exam  The exam instance to be updated.
     * @return bool True if the user is authorized to update the exam, false otherwise.
     */
    public function update(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('update exams'))
            || ($user->can('update exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to delete the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to delete exams,
     *
     * @param  User  $user  The user attempting to delete the exam.
     * @param  Exam  $exam  The exam instance to be deleted.
     * @return bool True if the user is authorized to delete the exam, false otherwise.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('delete exams'))
            || ($user->can('delete exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to restore the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to restore exams,
     *
     * @param  User  $user  The user attempting to restore the exam.
     * @param  Exam  $exam  The exam instance to be restored.
     * @return bool True if the user is authorized to restore the exam, false otherwise.
     */
    public function restore(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('restore exams'))
            || ($user->can('restore exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to permanently delete the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to force delete exams,
     *
     * @param  User  $user  The user attempting to force delete the exam.
     * @param  Exam  $exam  The exam instance to be permanently deleted.
     * @return bool True if the user is authorized to force delete the exam, false otherwise.
     */
    public function forceDelete(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('force delete exams'))
            || ($user->can('force delete exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to assign the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to assign exams,
     *
     * @param  User  $user  The user attempting to assign the exam.
     * @param  Exam  $exam  The exam instance to be assigned.
     * @return bool True if the user is authorized to assign the exam, false otherwise.
     */
    public function assign(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('assign exams'))
            || ($user->can('assign exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to review the specified exam.
     *
     * Typical checks performed by this policy may include:
     * - whether the user has permission to review exams,
     * - whether the user owns the exam,
     *
     * @param  User  $user  The user attempting to review the exam.
     * @param  Exam  $exam  The exam instance to be reviewed.
     * @return bool True if the user is authorized to review the exam, false otherwise.
     */
    public function review(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('correct exams'))
            || ($user->can('correct exams') && $exam->teacher_id === $user->id);
    }

    /**
     * Determine whether the given user is allowed to duplicate the specified exam.
     *
     * Teachers can only duplicate their own exams or exams they can view.
     * Admins can duplicate any exam.
     *
     * @param  User  $user  The user attempting to duplicate the exam.
     * @param  Exam  $exam  The exam instance to be duplicated.
     * @return bool True if the user is authorized to duplicate the exam, false otherwise.
     */
    public function duplicate(User $user, Exam $exam): bool
    {
        return ($user->can('view any exams') && $user->can('create exams'))
            || ($user->can('create exams') && $exam->teacher_id === $user->id);
    }
}
