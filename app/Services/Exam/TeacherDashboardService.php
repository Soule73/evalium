<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Teacher Dashboard Service
 *
 * Handles teacher dashboard statistics, recent exams, and pending reviews.
 */
class TeacherDashboardService
{
    /**
     * Get comprehensive dashboard statistics for a teacher
     *
     * Calculates:
     * - Total exams created
     * - Total questions across all exams
     * - Number of unique students evaluated
     * - Average score percentage across all submitted assignments
     *
     * @param  User  $teacher  The teacher user
     * @return array{total_exams: int, total_questions: int, students_evaluated: int, average_score: float}
     */
    public function getDashboardStats(User $teacher): array
    {
        $exams = Exam::where('teacher_id', $teacher->id)
            ->withCount('questions')
            ->get();

        $examIds = $exams->pluck('id');

        $assignments = ExamAssignment::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'graded'])
            ->whereNotNull('score')
            ->get();

        $totalQuestions = $exams->sum('questions_count');
        $studentsEvaluated = $assignments->unique('student_id')->count();

        $totalScore = $assignments->sum('score');
        $totalPossible = $assignments->sum(function ($assignment) use ($exams) {
            $exam = $exams->firstWhere('id', $assignment->exam_id);

            return $exam?->total_points ?? 0;
        });

        $averageScore = $totalPossible > 0 ? round(($totalScore / $totalPossible) * 100, 2) : 0;

        return [
            'totalExams' => $exams->count(),
            'totalQuestions' => $totalQuestions,
            'studentsEvaluated' => $studentsEvaluated,
            'averageScore' => $averageScore,
        ];
    }

    /**
     * Get recent exams for a teacher with pagination and filters
     *
     * @param  User  $teacher  The teacher user
     * @param  int  $perPage  Number of items per page
     * @param  string|null  $status  Filter by active status ('1' for active)
     * @param  string|null  $search  Search term for title or description
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRecentExams(User $teacher, int $perPage = 10, ?string $status = null, ?string $search = null)
    {
        $query = Exam::where('teacher_id', $teacher->id)
            ->withCount('questions');

        if ($status !== null) {
            $query->where('is_active', $status === '1');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get recent exam assignments requiring review/grading
     *
     * Returns submitted assignments without scores that need teacher attention.
     *
     * @param  User  $teacher  The teacher user
     * @param  int  $limit  Maximum number of pending reviews to return
     * @return Collection Collection of pending review data
     */
    public function getPendingReviews(User $teacher, int $limit = 5): Collection
    {
        return ExamAssignment::whereHas('exam', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
            ->where('status', 'submitted')
            ->whereNull('score')
            ->with(['exam:id,title', 'student:id,name'])
            ->orderBy('submitted_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'examTitle' => $assignment->exam->title,
                    'studentName' => $assignment->student->name,
                    'submittedAt' => $assignment->submitted_at->format('Y-m-d H:i'),
                    'timeTaken' => $assignment->duration ? $this->formatDuration($assignment->duration) : 'N/A',
                ];
            });
    }

    /**
     * Get complete dashboard data for a teacher
     *
     * Aggregates statistics, recent exams, and pending reviews.
     *
     * @param  User  $teacher  The teacher user
     * @param  int  $perPage  Number of exams per page
     * @param  string|null  $status  Filter by exam status
     * @param  string|null  $search  Search term for exams
     * @return array{stats: array, recent_exams: \Illuminate\Contracts\Pagination\LengthAwarePaginator, pending_reviews: Collection}
     */
    public function getDashboardData(User $teacher, int $perPage = 10, ?string $status = null, ?string $search = null): array
    {
        $stats = $this->getDashboardStats($teacher);

        $recentExams = $this->getRecentExams($teacher, $perPage, $status, $search);

        $pendingReviews = $this->getPendingReviews($teacher);

        return [
            'stats' => $stats,
            'recentExams' => $recentExams,
            'pendingReviews' => $pendingReviews,
        ];
    }

    /**
     * Format duration in seconds to human-readable time format
     *
     * @param  int|null  $seconds  Duration in seconds
     * @return string Formatted duration (HH:MM:SS or MM:SS) or 'N/A'
     */
    private function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return 'N/A';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}
