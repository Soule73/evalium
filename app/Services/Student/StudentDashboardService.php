<?php

namespace App\Services\Student;

use App\Models\User;
use App\Services\Core\AssessmentStatsService;
use Illuminate\Support\Collection;

/**
 * Student Dashboard Service
 *
 * Centralizes all dashboard-related data preparation for students.
 * Uses AssessmentStatsService for statistics calculations.
 *
 * Single Responsibility: Prepare student dashboard data only
 */
class StudentDashboardService
{
  public function __construct(
    private readonly AssessmentStatsService $assessmentStatsService,
    private readonly StudentAssignmentQueryService $studentAssignmentQueryService
  ) {}

  /**
   * Get comprehensive dashboard statistics for a student
   *
   * @param  User  $student  The student
   * @return array Dashboard statistics
   */
  public function getDashboardStats(User $student): array
  {
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $studentProgress = $this->assessmentStatsService->calculateStudentProgress($student);

    $activeClasses = $student->classes()
      ->wherePivot('status', 'active')
      ->count();

    $upcomingAssessments = $assignments
      ->filter(fn($assignment) => $assignment->started_at === null)
      ->take(5);

    $recentAssessments = $assignments
      ->filter(fn($assignment) => $assignment->submitted_at !== null)
      ->sortByDesc('submitted_at')
      ->take(5);

    $inProgress = $assignments->filter(function ($assignment) {
      return $assignment->started_at !== null && $assignment->submitted_at === null;
    })->count();

    $notStarted = $assignments->filter(function ($assignment) {
      return $assignment->started_at === null;
    })->count();

    return [
      'totalAssessments' => $studentProgress['total_assessments'],
      'completedAssessments' => $studentProgress['completed_assessments'],
      'pendingAssessments' => $notStarted,
      'inProgressAssessments' => $inProgress,
      'averageScore' => $studentProgress['average_score'],
      'completionRate' => $studentProgress['total_assessments'] > 0
        ? round(($studentProgress['completed_assessments'] / $studentProgress['total_assessments']) * 100, 2)
        : 0.0,
      'activeClasses' => $activeClasses,
      'upcomingAssessments' => $upcomingAssessments->values()->toArray(),
      'recentAssessments' => $recentAssessments->values()->toArray(),
    ];
  }

  /**
   * Get student performance summary
   *
   * @param  User  $student  The student
   * @return array Performance metrics
   */
  public function getPerformanceSummary(User $student): array
  {
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $gradedAssignments = $assignments->filter(function ($assignment) {
      return $assignment->score !== null;
    });

    if ($gradedAssignments->isEmpty()) {
      return [
        'totalGraded' => 0,
        'averageScore' => null,
        'highestScore' => null,
        'lowestScore' => null,
        'passingRate' => 0,
      ];
    }

    $scores = $gradedAssignments->pluck('score');
    $passingThreshold = 50;
    $passingCount = $scores->filter(fn($score) => $score >= $passingThreshold)->count();

    return [
      'totalGraded' => $gradedAssignments->count(),
      'averageScore' => round($scores->avg(), 2),
      'highestScore' => round($scores->max(), 2),
      'lowestScore' => round($scores->min(), 2),
      'passingRate' => $gradedAssignments->count() > 0
        ? round(($passingCount / $gradedAssignments->count()) * 100, 2)
        : 0,
    ];
  }

  /**
   * Get student activity timeline
   *
   * @param  User  $student  The student
   * @param  int  $limit  Number of recent activities
   * @return Collection Recent activities
   */
  public function getRecentActivity(User $student, int $limit = 10): Collection
  {
    /** @var \App\Models\AssessmentAssignment $assignments */
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $activities = collect();

    foreach ($assignments as $assignment) {
      if ($assignment->submitted_at) {
        $activities->push([
          'type' => 'submission',
          'assessmentTitle' => $assignment->assessment->title,
          'timestamp' => $assignment->submitted_at,
          'details' => [
            'score' => $assignment->score,
            'status' => $assignment->status,
          ],
        ]);
      }

      if ($assignment->started_at && ! $assignment->submitted_at) {
        $activities->push([
          'type' => 'started',
          'assessmentTitle' => $assignment->assessment->title,
          'timestamp' => $assignment->started_at,
          'details' => [],
        ]);
      }
    }

    return $activities
      ->sortByDesc('timestamp')
      ->take($limit)
      ->values();
  }

  /**
   * Get subject-based performance breakdown
   *
   * @param  User  $student  The student
   * @return array Performance by subject
   */
  public function getSubjectPerformance(User $student): array
  {
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $gradedAssignments = $assignments->filter(function ($assignment) {
      return $assignment->score !== null && $assignment->assessment;
    });

    if ($gradedAssignments->isEmpty()) {
      return [];
    }

    $bySubject = $gradedAssignments->groupBy(function ($assignment) {
      return $assignment->assessment->subject ?? 'General';
    });

    return $bySubject->map(function ($subjectAssignments, $subject) {
      $scores = $subjectAssignments->pluck('score');

      return [
        'subject' => $subject,
        'totalAssessments' => $subjectAssignments->count(),
        'averageScore' => round($scores->avg(), 2),
        'highestScore' => round($scores->max(), 2),
        'lowestScore' => round($scores->min(), 2),
      ];
    })->values()->toArray();
  }

  /**
   * Get monthly progress chart data
   *
   * @param  User  $student  The student
   * @param  int  $months  Number of months to look back
   * @return array Chart data
   */
  public function getMonthlyProgress(User $student, int $months = 6): array
  {
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $gradedAssignments = $assignments->filter(function ($assignment) {
      return $assignment->submitted_at && $assignment->score !== null;
    });

    $startDate = now()->subMonths($months)->startOfMonth();

    $monthlyData = $gradedAssignments
      ->filter(function ($assignment) use ($startDate) {
        return $assignment->submitted_at >= $startDate;
      })
      ->groupBy(function ($assignment) {
        return $assignment->submitted_at->format('Y-m');
      })
      ->map(function ($monthAssignments, $month) {
        $scores = $monthAssignments->pluck('score');

        return [
          'month' => $month,
          'count' => $monthAssignments->count(),
          'averageScore' => round($scores->avg(), 2),
        ];
      })
      ->values()
      ->toArray();

    return $monthlyData;
  }

  /**
   * Get group-wise performance comparison
   *
   * @param  User  $student  The student
   * @return array Performance per group
   */
  public function getClassPerformance(User $student): array
  {
    $classes = $student->classes()
      ->wherePivot('status', 'active')
      ->with('assessments')
      ->get();

    return $classes->map(function ($class) use ($student) {
      $classAssessmentIds = $class->assessments->pluck('id');

      $classAssignments = $student->assessmentAssignments()
        ->whereIn('assessment_id', $classAssessmentIds)
        ->get();

      $completedAssignments = $classAssignments->filter(function ($assignment) {
        return $assignment->submitted_at !== null;
      });

      $gradedAssignments = $completedAssignments->filter(function ($assignment) {
        return $assignment->score !== null;
      });

      $averageScore = $gradedAssignments->isEmpty()
        ? null
        : round($gradedAssignments->pluck('score')->avg(), 2);

      return [
        'classId' => $class->id,
        'className' => $class->name,
        'totalAssessments' => $classAssessmentIds->count(),
        'completedAssessments' => $completedAssignments->count(),
        'averageScore' => $averageScore,
        'completionRate' => $classAssessmentIds->count() > 0
          ? round(($completedAssignments->count() / $classAssessmentIds->count()) * 100, 2)
          : 0,
      ];
    })->toArray();
  }

  /**
   * Get exam status breakdown
   *
   * @param  User  $student  The student
   * @return array Status counts
   */
  public function getAssessmentStatusBreakdown(User $student): array
  {
    $assignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($student);

    $statusCounts = [
      'notStarted' => 0,
      'inProgress' => 0,
      'submitted' => 0,
      'graded' => 0,
    ];

    foreach ($assignments as $assignment) {
      if ($assignment->started_at === null) {
        $statusCounts['notStarted']++;
      } elseif ($assignment->submitted_at === null) {
        $statusCounts['inProgress']++;
      } elseif ($assignment->status === 'graded') {
        $statusCounts['graded']++;
      } else {
        $statusCounts['submitted']++;
      }
    }

    return $statusCounts;
  }
}
