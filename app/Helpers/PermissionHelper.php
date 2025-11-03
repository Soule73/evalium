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
    /**
     * Méthode centrale pour vérifier les permissions en utilisant hasPermissionTo() de Spatie.
     * Cette méthode garantit une vérification cohérente des permissions à travers toute l'application.
     *
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(string $permission): bool
    {
        return Auth::check() && Auth::user()->hasPermissionTo($permission);
    }

    // ==================== User Permissions ====================

    public static function canViewUsers(): bool
    {
        return self::hasPermission('view users');
    }

    public static function canCreateUsers(): bool
    {
        return self::hasPermission('create users');
    }

    public static function canUpdateUsers(): bool
    {
        return self::hasPermission('update users');
    }

    public static function canDeleteUsers(): bool
    {
        return self::hasPermission('delete users');
    }

    public static function canRestoreUsers(): bool
    {
        return self::hasPermission('restore users');
    }

    public static function canForceDeleteUsers(): bool
    {
        return self::hasPermission('force delete users');
    }

    public static function canManageStudents(): bool
    {
        return self::hasPermission('manage students');
    }

    public static function canManageTeachers(): bool
    {
        return self::hasPermission('manage teachers');
    }

    public static function canManageAdmins(): bool
    {
        return self::hasPermission('manage admins');
    }

    public static function canToggleUserStatus(): bool
    {
        return self::hasPermission('toggle user status');
    }

    // ==================== Exam Permissions ====================

    public static function canViewExams(): bool
    {
        return self::hasPermission('view exams');
    }

    public static function canViewAnyExams(): bool
    {
        return self::hasPermission('view any exams');
    }

    public static function canCreateExams(): bool
    {
        return self::hasPermission('create exams');
    }

    public static function canUpdateExams(): bool
    {
        return self::hasPermission('update exams');
    }

    public static function canDeleteExams(): bool
    {
        return self::hasPermission('delete exams');
    }

    public static function canRestoreExams(): bool
    {
        return self::hasPermission('restore exams');
    }

    public static function canForceDeleteExams(): bool
    {
        return self::hasPermission('force delete exams');
    }

    public static function canPublishExams(): bool
    {
        return self::hasPermission('publish exams');
    }

    public static function canAssignExams(): bool
    {
        return self::hasPermission('assign exams');
    }

    public static function canCorrectExams(): bool
    {
        return self::hasPermission('correct exams');
    }

    public static function canViewExamResults(): bool
    {
        return self::hasPermission('view exam results');
    }

    // ==================== Question Permissions ====================

    public static function canViewQuestions(): bool
    {
        return self::hasPermission('view questions');
    }

    public static function canCreateQuestions(): bool
    {
        return self::hasPermission('create questions');
    }

    public static function canUpdateQuestions(): bool
    {
        return self::hasPermission('update questions');
    }

    public static function canDeleteQuestions(): bool
    {
        return self::hasPermission('delete questions');
    }

    // ==================== Answer Permissions ====================

    public static function canViewAnswers(): bool
    {
        return self::hasPermission('view answers');
    }

    public static function canCreateAnswers(): bool
    {
        return self::hasPermission('create answers');
    }

    public static function canUpdateAnswers(): bool
    {
        return self::hasPermission('update answers');
    }

    public static function canDeleteAnswers(): bool
    {
        return self::hasPermission('delete answers');
    }

    public static function canGradeAnswers(): bool
    {
        return self::hasPermission('grade answers');
    }

    // ==================== ExamAssignment Permissions ====================

    public static function canViewAssignments(): bool
    {
        return self::hasPermission('view assignments');
    }

    public static function canCreateAssignments(): bool
    {
        return self::hasPermission('create assignments');
    }

    public static function canUpdateAssignments(): bool
    {
        return self::hasPermission('update assignments');
    }

    public static function canDeleteAssignments(): bool
    {
        return self::hasPermission('delete assignments');
    }

    public static function canSubmitAssignments(): bool
    {
        return self::hasPermission('submit assignments');
    }

    public static function canGradeAssignments(): bool
    {
        return self::hasPermission('grade assignments');
    }

    // ==================== Group Permissions ====================

    public static function canViewGroups(): bool
    {
        return self::hasPermission('view groups');
    }

    public static function canCreateGroups(): bool
    {
        return self::hasPermission('create groups');
    }

    public static function canUpdateGroups(): bool
    {
        return self::hasPermission('update groups');
    }

    public static function canDeleteGroups(): bool
    {
        return self::hasPermission('delete groups');
    }

    public static function canManageGroupStudents(): bool
    {
        return self::hasPermission('manage group students');
    }

    public static function canAssignGroupExams(): bool
    {
        return self::hasPermission('assign group exams');
    }

    public static function canToggleGroupStatus(): bool
    {
        return self::hasPermission('toggle group status');
    }

    // ==================== Level Permissions ====================

    public static function canViewLevels(): bool
    {
        return self::hasPermission('view levels');
    }

    public static function canCreateLevels(): bool
    {
        return self::hasPermission('create levels');
    }

    public static function canUpdateLevels(): bool
    {
        return self::hasPermission('update levels');
    }

    public static function canDeleteLevels(): bool
    {
        return self::hasPermission('delete levels');
    }

    public static function canManageLevels(): bool
    {
        return self::hasPermission('update levels') || self::hasPermission('delete levels');
    }

    // ==================== Role & Permission Management ====================

    public static function canViewRoles(): bool
    {
        return self::hasPermission('view roles');
    }

    public static function canCreateRoles(): bool
    {
        return self::hasPermission('create roles');
    }

    public static function canUpdateRoles(): bool
    {
        return self::hasPermission('update roles');
    }

    public static function canDeleteRoles(): bool
    {
        return self::hasPermission('delete roles');
    }

    public static function canAssignPermissions(): bool
    {
        return self::hasPermission('assign permissions');
    }

    public static function canViewPermissions(): bool
    {
        return self::hasPermission('view permissions');
    }

    public static function canCreatePermissions(): bool
    {
        return self::hasPermission('create permissions');
    }

    public static function canDeletePermissions(): bool
    {
        return self::hasPermission('delete permissions');
    }

    // ==================== Dashboard & Reports ====================

    public static function canViewAdminDashboard(): bool
    {
        return self::hasPermission('view admin dashboard');
    }

    public static function canViewTeacherDashboard(): bool
    {
        return self::hasPermission('view teacher dashboard');
    }

    public static function canViewStudentDashboard(): bool
    {
        return self::hasPermission('view student dashboard');
    }

    public static function canViewReports(): bool
    {
        return self::hasPermission('view reports');
    }

    public static function canExportReports(): bool
    {
        return self::hasPermission('export reports');
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

        if (self::hasPermission('view admin dashboard')) {
            return 'admin';
        } elseif (self::hasPermission('view teacher dashboard')) {
            return 'teacher';
        } elseif (self::hasPermission('view student dashboard')) {
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
            'admin' => route('dashboard'),
            'teacher' => route('dashboard'),
            'student' => route('dashboard'),
            default => route('dashboard')
        };
    }
}
