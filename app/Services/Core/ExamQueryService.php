<?php

namespace App\Services\Core;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Exam Query Service - Handle exam queries for teachers and admins
 *
 * Single Responsibility: Build and execute exam queries with filtering
 * Used by teacher/admin controllers to list and search exams
 *
 * IMPORTANT: Student-related queries are in StudentAssignmentQueryService
 * IMPORTANT: Statistics are in ExamStatsService
 */
class ExamQueryService
{
    /**
     * Get paginated exams for a teacher or all exams for admins
     */
    public function getExams(
        ?int $teacherId,
        int $perPage = 10,
        ?bool $status = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = Exam::query();

        /** @var \App\Models\User $currentUser */
        // $currentUser = Auth::user();

        // If user can view any exams (admin), don't filter by teacher
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        return $query
            ->withCount(['questions', 'assignments'])
            ->latest()
            ->when(
                $search,
                fn ($query) => $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
            )
            ->when(
                $status !== null,
                fn ($query) => $query->where('is_active', $status)
            )
            ->paginate($perPage)
            ->withQueryString();
    }
}
