<?php

namespace App\Services\Exam;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Service pour gérer le tableau de bord des enseignants
 * 
 * @package App\Services\Exam
 */
class TeacherDashboardService
{
    /**
     * Obtenir les statistiques du tableau de bord professeur
     */
    public function getDashboardStats(User $teacher): array
    {
        $teacherExams = $this->getExams($teacher);
        $teacherExamIds = $teacherExams->pluck('id');

        return [
            'total_exams' => $teacherExams->count(),
            'total_questions' => $this->getTotalQuestions($teacherExamIds),
            'students_evaluated' => $this->getStudentsEvaluatedCount($teacherExamIds),
            'average_score' => $this->calculateAverageScore($teacherExamIds),
        ];
    }

    /**
     * Obtenir les examens récents du professeur avec pagination
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
     * Obtenir les assignations récentes nécessitant une correction
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
                    'exam_title' => $assignment->exam->title,
                    'student_name' => $assignment->student->name,
                    'submitted_at' => $assignment->submitted_at->format('Y-m-d H:i'),
                    'time_taken' => $assignment->duration ? $this->formatDuration($assignment->duration) : 'N/A',
                ];
            });
    }

    /**
     * Obtenir les données complètes du dashboard professeur
     */
    public function getDashboardData(User $teacher, int $perPage = 10, ?string $status = null, ?string $search = null): array
    {
        return [
            'stats' => $this->getDashboardStats($teacher),
            'recent_exams' => $this->getRecentExams($teacher, $perPage, $status, $search),
            'pending_reviews' => $this->getPendingReviews($teacher),
        ];
    }

    /**
     * Méthodes privées pour les calculs
     */
    private function getExams(User $teacher): Collection
    {
        return Exam::where('teacher_id', $teacher->id)->get();
    }

    private function getTotalQuestions(Collection $examIds): int
    {
        return Question::whereIn('exam_id', $examIds)->count();
    }

    private function getStudentsEvaluatedCount(Collection $examIds): int
    {
        return ExamAssignment::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'graded'])
            ->distinct('student_id')
            ->count();
    }

    private function calculateAverageScore(Collection $examIds): float
    {
        $assignments = ExamAssignment::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'graded'])
            ->whereNotNull('score')
            ->get();

        if ($assignments->isEmpty()) {
            return 0;
        }

        $totalScore = $assignments->sum('score');
        $totalPossible = $assignments->sum(function ($assignment) {
            return $assignment->exam->total_points ?? 0;
        });

        return $totalPossible > 0 ? round(($totalScore / $totalPossible) * 100, 2) : 0;
    }

    private function formatDuration(?int $seconds): string
    {
        if (!$seconds) {
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
