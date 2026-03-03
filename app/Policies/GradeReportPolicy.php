<?php

namespace App\Policies;

use App\Enums\GradeReportStatus;
use App\Models\GradeReport;
use App\Models\User;

/**
 * Authorization rules for the GradeReport resource.
 *
 * Admins can manage all reports.
 * Teachers can view and update remarks on reports for their assigned class-subjects.
 * Students can only view and download their own published reports.
 */
class GradeReportPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the teacher is assigned to any subject in the report's class.
     */
    private function isTeacherForReport(User $user, GradeReport $report): bool
    {
        return $report->enrollment?->class?->classSubjects()
            ->where('teacher_id', $user->id)
            ->exists() ?? false;
    }

    /**
     * Determine whether the user can view any grade reports.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasRole('teacher')
            || $user->hasRole('student');
    }

    /**
     * Determine whether the user can view the grade report.
     */
    public function view(User $user, GradeReport $report): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $this->isTeacherForReport($user, $report);
        }

        if ($user->hasRole('student')) {
            return $report->enrollment?->student_id === $user->id
                && $report->status === GradeReportStatus::Published;
        }

        return false;
    }

    /**
     * Determine whether the user can generate grade reports for a class.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update subject remarks on the report.
     */
    public function updateRemarks(User $user, GradeReport $report): bool
    {
        if ($report->status !== GradeReportStatus::Draft) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $this->isTeacherForReport($user, $report);
        }

        return false;
    }

    /**
     * Determine whether the user can update the general remark on the report.
     */
    public function updateGeneralRemark(User $user, GradeReport $report): bool
    {
        return $this->isAdmin($user) && $report->status === GradeReportStatus::Draft;
    }

    /**
     * Determine whether the user can validate the report.
     */
    public function validate(User $user, GradeReport $report): bool
    {
        return $this->isAdmin($user) && $report->status === GradeReportStatus::Draft;
    }

    /**
     * Determine whether the user can publish the report.
     */
    public function publish(User $user, GradeReport $report): bool
    {
        return $this->isAdmin($user) && $report->status === GradeReportStatus::Validated;
    }

    /**
     * Determine whether the user can validate reports in batch.
     */
    public function validateBatch(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can publish reports in batch.
     */
    public function publishBatch(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can download reports in batch.
     */
    public function downloadBatch(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can download the report PDF.
     */
    public function download(User $user, GradeReport $report): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($report->status === GradeReportStatus::Published && $user->hasRole('student')) {
            return $report->enrollment?->student_id === $user->id;
        }

        if ($user->hasRole('teacher')) {
            return $this->isTeacherForReport($user, $report);
        }

        return false;
    }
}
