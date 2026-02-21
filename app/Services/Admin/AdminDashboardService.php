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
     * @param  int|null  $academicYearId  Academic year filter
     * @return array Statistics with user counts by role and academic year data
     */
    public function getDashboardStats(?int $academicYearId = null): array
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

        $classesCount = 0;
        $enrollmentsCount = 0;
        $classSubjectsCount = 0;

        if ($academicYearId) {
            $yearRow = DB::table('classes')
                ->where('classes.academic_year_id', $academicYearId)
                ->leftJoin('enrollments', 'enrollments.class_id', '=', 'classes.id')
                ->leftJoin('class_subjects', 'class_subjects.class_id', '=', 'classes.id')
                ->selectRaw('
                    COUNT(DISTINCT classes.id) as classes_count,
                    COUNT(DISTINCT enrollments.id) as enrollments_count,
                    COUNT(DISTINCT class_subjects.id) as class_subjects_count
                ')
                ->first();

            $classesCount = (int) ($yearRow->classes_count ?? 0);
            $enrollmentsCount = (int) ($yearRow->enrollments_count ?? 0);
            $classSubjectsCount = (int) ($yearRow->class_subjects_count ?? 0);
        }

        return [
            'totalUsers' => $userCounts->total_users ?? 0,
            'studentsCount' => $userCounts->students_count ?? 0,
            'teachersCount' => $userCounts->teachers_count ?? 0,
            'adminsCount' => $userCounts->admins_count ?? 0,
            'classesCount' => $classesCount,
            'enrollmentsCount' => $enrollmentsCount,
            'classSubjectsCount' => $classSubjectsCount,
        ];
    }

    /**
     * Get complete admin dashboard data
     *
     * @param  int|null  $academicYearId  Optional academic year filter
     * @return array Dashboard data with statistics
     */
    public function getDashboardData(?int $academicYearId = null): array
    {
        return [
            'stats' => $this->getDashboardStats($academicYearId),
        ];
    }
}
