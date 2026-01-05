<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

/**
 * Admin Dashboard Service - Provide statistics and data for admin dashboard
 *
 * Single Responsibility: Aggregate and format admin dashboard data
 * Optimized with raw SQL queries for performance
 */
class AdminDashboardService
{
    /**
     * Get admin dashboard statistics with optimized single query
     *
     * @return array Statistics with user counts by role
     */
    public function getDashboardStats(): array
    {
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
            'totalUsers' => $userCounts->total_users ?? 0,
            'studentsCount' => $userCounts->students_count ?? 0,
            'teachersCount' => $userCounts->teachers_count ?? 0,
            'adminsCount' => $userCounts->admins_count ?? 0,
        ];
    }

    /**
     * Get complete admin dashboard data
     *
     * @return array Dashboard data with statistics
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => $this->getDashboardStats(),
        ];
    }
}
