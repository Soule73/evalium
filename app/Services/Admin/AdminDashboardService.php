<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminDashboardService
{
    /**
     * Obtenir les statistiques du tableau de bord administrateur
     * Optimisé avec une seule requête SQL pour les counts par rôle
     */
    public function getDashboardStats(): array
    {
        // Utilisation d'une requête brute optimisée pour compter les utilisateurs par rôle
        $userCounts = DB::table('users')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->selectRaw('
                COUNT(DISTINCT users.id) as total_users,
                COUNT(DISTINCT CASE WHEN roles.name = "student" THEN users.id END) as students_count,
                COUNT(DISTINCT CASE WHEN roles.name = "teacher" THEN users.id END) as teachers_count,
                COUNT(DISTINCT CASE WHEN roles.name = "admin" OR roles.name = "super_admin" THEN users.id END) as admins_count
            ')
            ->first();

        return [
            'total_users' => $userCounts->total_users ?? 0,
            'students_count' => $userCounts->students_count ?? 0,
            'teachers_count' => $userCounts->teachers_count ?? 0,
            'admins_count' => $userCounts->admins_count ?? 0,
        ];
    }
    /**
     * Obtenir les données complètes du dashboard administrateur
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => $this->getDashboardStats(),
            //
        ];
    }
}
