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
     * Get users grouped by role for donut chart.
     *
     * @param  int|null  $academicYearId  Unused, kept for API consistency
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function getUsersByRoleChart(?int $academicYearId = null): array
    {
        $rows = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('users', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->whereNull('users.deleted_at')
            ->groupBy('roles.name')
            ->select('roles.name as role', DB::raw('COUNT(DISTINCT users.id) as count'))
            ->get();

        $roleColors = [
            'student' => '#10b981',
            'teacher' => '#3b82f6',
            'admin' => '#8b5cf6',
            'super_admin' => '#4f46e5',
        ];

        return $rows->map(fn ($row) => [
            'name' => ucfirst(str_replace('_', ' ', $row->role)),
            'value' => (int) $row->count,
            'color' => $roleColors[$row->role] ?? '#6b7280',
        ])->values()->toArray();
    }

    /**
     * Get classes grouped by level for bar chart.
     *
     * @param  int|null  $academicYearId  Academic year filter
     * @return array<int, array{name: string, value: int}>
     */
    public function getClassesByLevelChart(?int $academicYearId = null): array
    {
        $query = DB::table('classes')
            ->join('levels', 'classes.level_id', '=', 'levels.id');

        if ($academicYearId) {
            $query->where('classes.academic_year_id', $academicYearId);
        }

        return $query
            ->groupBy('levels.id', 'levels.name', 'levels.order')
            ->orderBy('levels.order')
            ->select('levels.name', DB::raw('COUNT(classes.id) as value'))
            ->get()
            ->toArray();
    }

    /**
     * Get enrollment vs capacity per class for bar chart.
     *
     * @param  int|null  $academicYearId  Academic year filter
     * @return array<int, array{name: string, enrolled: int, capacity: int}>
     */
    public function getEnrollmentCapacityChart(?int $academicYearId = null): array
    {
        $query = DB::table('classes')
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.class_id', '=', 'classes.id')
                    ->where('enrollments.status', '=', 'active');
            });

        if ($academicYearId) {
            $query->where('classes.academic_year_id', $academicYearId);
        }

        return $query
            ->groupBy('classes.id', 'classes.name', 'classes.max_students')
            ->orderBy('classes.name')
            ->select(
                'classes.name',
                DB::raw('COUNT(DISTINCT enrollments.id) as enrolled'),
                DB::raw('COALESCE(classes.max_students, 0) as capacity')
            )
            ->get()
            ->toArray();
    }

    /**
     * Get assessment counts by publication status.
     *
     * @param  int|null  $academicYearId  Academic year filter
     * @return array{published: int, draft: int, total: int}
     */
    public function getAssessmentStatusCounts(?int $academicYearId = null): array
    {
        $query = DB::table('assessments')
            ->join('class_subjects', 'assessments.class_subject_id', '=', 'class_subjects.id')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.id');

        if ($academicYearId) {
            $query->where('classes.academic_year_id', $academicYearId);
        }

        $row = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN assessments.is_published = 1 THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN assessments.is_published = 0 THEN 1 ELSE 0 END) as draft
        ')->first();

        return [
            'published' => (int) ($row->published ?? 0),
            'draft' => (int) ($row->draft ?? 0),
            'total' => (int) ($row->total ?? 0),
        ];
    }

    /**
     * Get all chart data for the admin dashboard.
     *
     * @param  int|null  $academicYearId  Academic year filter
     * @return array Chart datasets for frontend rendering
     */
    public function getChartData(?int $academicYearId = null): array
    {
        return [
            'usersByRole' => $this->getUsersByRoleChart($academicYearId),
            'classesByLevel' => $this->getClassesByLevelChart($academicYearId),
            'enrollmentCapacity' => $this->getEnrollmentCapacityChart($academicYearId),
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
        $stats = $this->getDashboardStats($academicYearId);
        $assessmentCounts = $this->getAssessmentStatusCounts($academicYearId);

        return [
            'stats' => array_merge($stats, [
                'assessmentsCount' => $assessmentCounts['total'],
                'publishedCount' => $assessmentCounts['published'],
                'draftCount' => $assessmentCounts['draft'],
            ]),
        ];
    }
}
