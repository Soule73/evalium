<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class AdminDashboardService
{
    /**
     * Obtenir les statistiques du tableau de bord administrateur
     */
    public function getDashboardStats(): array
    {
        return [
            'total_users' => User::count(),
            'students_count' => $this->getUserCountByRole('student'),
            'teachers_count' => $this->getUserCountByRole('teacher'),
            'admins_count' => $this->getUserCountByRole('admin'),
            'total_exams' => Exam::count(),
            'active_exams' => Exam::where('is_active', true)->count(),
            'total_assignments' => ExamAssignment::count(),
            'completed_assignments' => ExamAssignment::whereIn('status', ['submitted', 'graded'])->count(),
        ];
    }

    /**
     * Obtenir les utilisateurs récents
     */
    public function getRecentUsers(int $limit = 5): Collection
    {
        return User::with('roles')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'Aucun rôle',
                    'created_at' => $user->created_at->format('Y-m-d H:i'),
                    'email_verified' => $user->email_verified_at !== null,
                ];
            });
    }

    /**
     * Obtenir les examens récents de tous les professeurs
     */
    public function getRecentExams(int $limit = 5): Collection
    {
        return Exam::with(['teacher:id,name', 'assignments'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'teacher_name' => $exam->teacher->name,
                    'questions_count' => $exam->questions_count,
                    'assignments_count' => $exam->assignments->count(),
                    'created_at' => $exam->created_at->format('Y-m-d H:i'),
                    'status' => $exam->is_active ? 'active' : 'inactive',
                ];
            });
    }

    /**
     * Obtenir les statistiques d'activité récente
     */
    public function getActivityStats(): array
    {
        $currentDate = now();
        $weekAgo = $currentDate->copy()->subWeek();
        $monthAgo = $currentDate->copy()->subMonth();

        return [
            'new_users_this_week' => User::where('created_at', '>=', $weekAgo)->count(),
            'new_users_this_month' => User::where('created_at', '>=', $monthAgo)->count(),
            'new_exams_this_week' => Exam::where('created_at', '>=', $weekAgo)->count(),
            'new_exams_this_month' => Exam::where('created_at', '>=', $monthAgo)->count(),
            'assignments_submitted_this_week' => ExamAssignment::where('submitted_at', '>=', $weekAgo)->count(),
            'assignments_submitted_this_month' => ExamAssignment::where('submitted_at', '>=', $monthAgo)->count(),
        ];
    }

    /**
     * Obtenir la répartition des rôles utilisateurs
     */
    public function getUserRoleDistribution(): array
    {
        $roles = Role::withCount('users')->get();

        return $roles->mapWithKeys(function ($role) {
            return [$role->name => $role->users_count];
        })->toArray();
    }

    /**
     * Obtenir les données complètes du dashboard administrateur
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => $this->getDashboardStats(),
            'activity_stats' => $this->getActivityStats(),
            'recent_exams' => $this->getRecentExams(),
            'role_distribution' => $this->getUserRoleDistribution(),
        ];
    }

    /**
     * Obtenir les métriques de performance système
     */
    public function getSystemMetrics(): array
    {
        return [
            'total_database_records' => $this->getTotalDatabaseRecords(),
            'average_exam_completion_rate' => $this->getAverageExamCompletionRate(),
            'most_active_teachers' => $this->getMostActiveTeachers(3),
            'most_engaged_students' => $this->getMostEngagedStudents(3),
        ];
    }

    /**
     * Méthodes privées pour les calculs
     */
    private function getUserCountByRole(string $roleName): int
    {
        return User::role($roleName)->count();
    }

    private function getTotalDatabaseRecords(): int
    {
        return User::count() +
            Exam::count() +
            ExamAssignment::count() +
            \App\Models\Question::count() +
            \App\Models\Answer::count();
    }

    private function getAverageExamCompletionRate(): float
    {
        $totalAssignments = ExamAssignment::count();
        $completedAssignments = ExamAssignment::whereIn('status', ['submitted', 'graded'])->count();

        return $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 2) : 0;
    }

    private function getMostActiveTeachers(int $limit): Collection
    {
        return User::role('teacher')
            ->withCount('exams')
            ->orderBy('exams_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($teacher) {
                return [
                    'name' => $teacher->name,
                    'exams_count' => $teacher->exams_count,
                ];
            });
    }

    private function getMostEngagedStudents(int $limit): Collection
    {
        return User::role('student')
            ->withCount(['examAssignments as completed_assignments_count' => function ($query) {
                $query->whereIn('status', ['submitted', 'graded']);
            }])
            ->orderBy('completed_assignments_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($student) {
                return [
                    'name' => $student->name,
                    'completed_assignments' => $student->completed_assignments_count,
                ];
            });
    }
}
