<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

/**
 * Helper class pour vérifier les permissions des utilisateurs.
 * 
 * IMPORTANT: Ce helper est maintenant ENTIÈREMENT basé sur les PERMISSIONS
 * et non sur les rôles, permettant la création flexible de rôles personnalisés.
 * 
 * Chaque méthode correspond à une permission définie dans RoleAndPermissionSeeder.
 */
class PermissionHelper
{
    // ==================== User Permissions ====================

    public static function canViewUsers(): bool
    {
        return Auth::check() && Auth::user()->can('view users');
    }

    public static function canCreateUsers(): bool
    {
        return Auth::check() && Auth::user()->can('create users');
    }

    public static function canUpdateUsers(): bool
    {
        return Auth::check() && Auth::user()->can('update users');
    }

    public static function canDeleteUsers(): bool
    {
        return Auth::check() && Auth::user()->can('delete users');
    }

    public static function canRestoreUsers(): bool
    {
        return Auth::check() && Auth::user()->can('restore users');
    }

    public static function canForceDeleteUsers(): bool
    {
        return Auth::check() && Auth::user()->can('force delete users');
    }

    public static function canManageStudents(): bool
    {
        return Auth::check() && Auth::user()->can('manage students');
    }

    public static function canManageTeachers(): bool
    {
        return Auth::check() && Auth::user()->can('manage teachers');
    }

    public static function canManageAdmins(): bool
    {
        return Auth::check() && Auth::user()->can('manage admins');
    }

    public static function canToggleUserStatus(): bool
    {
        return Auth::check() && Auth::user()->can('toggle user status');
    }

    // ==================== Exam Permissions ====================

    public static function canViewExams(): bool
    {
        return Auth::check() && Auth::user()->can('view exams');
    }

    public static function canViewAnyExams(): bool
    {
        return Auth::check() && Auth::user()->can('view any exams');
    }

    public static function canCreateExams(): bool
    {
        return Auth::check() && Auth::user()->can('create exams');
    }

    public static function canUpdateExams(): bool
    {
        return Auth::check() && Auth::user()->can('update exams');
    }

    public static function canDeleteExams(): bool
    {
        return Auth::check() && Auth::user()->can('delete exams');
    }

    public static function canRestoreExams(): bool
    {
        return Auth::check() && Auth::user()->can('restore exams');
    }

    public static function canForceDeleteExams(): bool
    {
        return Auth::check() && Auth::user()->can('force delete exams');
    }

    public static function canPublishExams(): bool
    {
        return Auth::check() && Auth::user()->can('publish exams');
    }

    public static function canAssignExams(): bool
    {
        return Auth::check() && Auth::user()->can('assign exams');
    }

    public static function canCorrectExams(): bool
    {
        return Auth::check() && Auth::user()->can('correct exams');
    }

    public static function canViewExamResults(): bool
    {
        return Auth::check() && Auth::user()->can('view exam results');
    }

    // ==================== Question Permissions ====================

    public static function canViewQuestions(): bool
    {
        return Auth::check() && Auth::user()->can('view questions');
    }

    public static function canCreateQuestions(): bool
    {
        return Auth::check() && Auth::user()->can('create questions');
    }

    public static function canUpdateQuestions(): bool
    {
        return Auth::check() && Auth::user()->can('update questions');
    }

    public static function canDeleteQuestions(): bool
    {
        return Auth::check() && Auth::user()->can('delete questions');
    }

    // ==================== Answer Permissions ====================

    public static function canViewAnswers(): bool
    {
        return Auth::check() && Auth::user()->can('view answers');
    }

    public static function canCreateAnswers(): bool
    {
        return Auth::check() && Auth::user()->can('create answers');
    }

    public static function canUpdateAnswers(): bool
    {
        return Auth::check() && Auth::user()->can('update answers');
    }

    public static function canDeleteAnswers(): bool
    {
        return Auth::check() && Auth::user()->can('delete answers');
    }

    public static function canGradeAnswers(): bool
    {
        return Auth::check() && Auth::user()->can('grade answers');
    }

    // ==================== ExamAssignment Permissions ====================

    public static function canViewAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('view assignments');
    }

    public static function canCreateAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('create assignments');
    }

    public static function canUpdateAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('update assignments');
    }

    public static function canDeleteAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('delete assignments');
    }

    public static function canSubmitAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('submit assignments');
    }

    public static function canGradeAssignments(): bool
    {
        return Auth::check() && Auth::user()->can('grade assignments');
    }

    // ==================== Group Permissions ====================

    public static function canViewGroups(): bool
    {
        return Auth::check() && Auth::user()->can('view groups');
    }

    public static function canCreateGroups(): bool
    {
        return Auth::check() && Auth::user()->can('create groups');
    }

    public static function canUpdateGroups(): bool
    {
        return Auth::check() && Auth::user()->can('update groups');
    }

    public static function canDeleteGroups(): bool
    {
        return Auth::check() && Auth::user()->can('delete groups');
    }

    public static function canManageGroupStudents(): bool
    {
        return Auth::check() && Auth::user()->can('manage group students');
    }

    public static function canAssignGroupExams(): bool
    {
        return Auth::check() && Auth::user()->can('assign group exams');
    }

    public static function canToggleGroupStatus(): bool
    {
        return Auth::check() && Auth::user()->can('toggle group status');
    }

    // ==================== Level Permissions ====================

    public static function canViewLevels(): bool
    {
        return Auth::check() && Auth::user()->can('view levels');
    }

    public static function canCreateLevels(): bool
    {
        return Auth::check() && Auth::user()->can('create levels');
    }

    public static function canUpdateLevels(): bool
    {
        return Auth::check() && Auth::user()->can('update levels');
    }

    public static function canDeleteLevels(): bool
    {
        return Auth::check() && Auth::user()->can('delete levels');
    }

    public static function canManageLevels(): bool
    {
        return Auth::check() && Auth::user()->can('manage levels');
    }

    // ==================== Role & Permission Management ====================

    public static function canViewRoles(): bool
    {
        return Auth::check() && Auth::user()->can('view roles');
    }

    public static function canCreateRoles(): bool
    {
        return Auth::check() && Auth::user()->can('create roles');
    }

    public static function canUpdateRoles(): bool
    {
        return Auth::check() && Auth::user()->can('update roles');
    }

    public static function canDeleteRoles(): bool
    {
        return Auth::check() && Auth::user()->can('delete roles');
    }

    public static function canAssignPermissions(): bool
    {
        return Auth::check() && Auth::user()->can('assign permissions');
    }

    public static function canViewPermissions(): bool
    {
        return Auth::check() && Auth::user()->can('view permissions');
    }

    public static function canCreatePermissions(): bool
    {
        return Auth::check() && Auth::user()->can('create permissions');
    }

    public static function canDeletePermissions(): bool
    {
        return Auth::check() && Auth::user()->can('delete permissions');
    }

    // ==================== Dashboard & Reports ====================

    public static function canViewAdminDashboard(): bool
    {
        return Auth::check() && Auth::user()->can('view admin dashboard');
    }

    public static function canViewTeacherDashboard(): bool
    {
        return Auth::check() && Auth::user()->can('view teacher dashboard');
    }

    public static function canViewStudentDashboard(): bool
    {
        return Auth::check() && Auth::user()->can('view student dashboard');
    }

    public static function canViewReports(): bool
    {
        return Auth::check() && Auth::user()->can('view reports');
    }

    public static function canExportReports(): bool
    {
        return Auth::check() && Auth::user()->can('export reports');
    }

    // ==================== Utility Methods ====================

    /**
     * Récupère toutes les permissions de l'utilisateur authentifié.
     *
     * @return array Tableau des noms de permissions
     */
    public static function getUserPermissions(): array
    {
        if (!Auth::check()) {
            return [];
        }

        return Auth::user()->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Récupère la liste complète des permissions définies dans l'application.
     *
     * @return array Tableau des noms de permissions
     */
    public static function getAllPermissions(): array
    {
        return Permission::orderBy('name')->pluck('name')->toArray();
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique.
     *
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(string $permission): bool
    {
        return Auth::check() && Auth::user()->can($permission);
    }

    /**
     * Détermine le type de dashboard approprié basé sur les permissions.
     * Priorité: admin > teacher > student
     * 
     * @return string|null
     */
    public static function getUserDashboardType(): ?string
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->can('view admin dashboard')) {
            return 'admin';
        } elseif ($user->can('view teacher dashboard')) {
            return 'teacher';
        } elseif ($user->can('view student dashboard')) {
            return 'student';
        }

        return null;
    }

    /**
     * Récupère la route du dashboard appropriée basée sur les permissions.
     *
     * @return string
     */
    public static function getDashboardRoute(): string
    {
        $type = self::getUserDashboardType();

        return match ($type) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'student' => route('student.dashboard'),
            default => route('dashboard')
        };
    }
}
