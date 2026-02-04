<?php

namespace App\Services\Core;

use App\Models\AssessmentAssignment;
use App\Models\User;

/**
 * Assessment Statistics Service
 *
 * Handles statistics calculations for assessments and student performance.
 * Single Responsibility: Calculate assessment-related statistics only.
 */
class AssessmentStatsService
{
    /**
     * Calculate comprehensive student progress metrics
     *
     * @param  User  $student  The student user
     * @return array Student progress statistics
     */
    public function calculateStudentProgress(User $student): array
    {
        $assignments = AssessmentAssignment::where('student_id', $student->id)
            ->with('assessment')
            ->get();

        $totalAssessments = $assignments->count();
        $completedAssessments = $assignments->whereNotNull('submitted_at')->count();

        $gradedAssignments = $assignments->whereNotNull('score');
        $averageScore = $gradedAssignments->isEmpty()
          ? null
          : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assessments' => $totalAssessments,
            'completed_assessments' => $completedAssessments,
            'average_score' => $averageScore,
            'pending_assessments' => $totalAssessments - $completedAssessments,
        ];
    }

    /**
     * Calculate assessment statistics for a specific assessment
     *
     * @param  int  $assessmentId  The assessment ID
     * @return array Assessment statistics
     */
    public function calculateAssessmentStats(int $assessmentId): array
    {
        $assignments = AssessmentAssignment::where('assessment_id', $assessmentId)->get();

        $totalAssigned = $assignments->count();
        $completed = $assignments->whereNotNull('submitted_at')->count();
        $inProgress = $assignments->whereNotNull('started_at')->whereNull('submitted_at')->count();
        $notStarted = $assignments->whereNull('started_at')->count();

        $gradedAssignments = $assignments->whereNotNull('score');
        $averageScore = $gradedAssignments->isEmpty()
          ? null
          : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assigned' => $totalAssigned,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'average_score' => $averageScore,
            'completion_rate' => $totalAssigned > 0
              ? round(($completed / $totalAssigned) * 100, 2)
              : 0.0,
        ];
    }

    /**
     * Calculate class-level assessment statistics
     *
     * @param  int  $classId  The class ID
     * @return array Class assessment statistics
     */
    public function calculateClassStats(int $classId): array
    {
        $assignments = AssessmentAssignment::whereHas('student.classes', function ($query) use ($classId) {
            $query->where('class_models.id', $classId);
        })->with('assessment')->get();

        $totalAssessments = $assignments->count();
        $completed = $assignments->whereNotNull('submitted_at')->count();
        $gradedAssignments = $assignments->whereNotNull('score');

        $averageScore = $gradedAssignments->isEmpty()
          ? null
          : round($gradedAssignments->avg('score'), 2);

        return [
            'total_assessments' => $totalAssessments,
            'completed' => $completed,
            'average_score' => $averageScore,
            'completion_rate' => $totalAssessments > 0
              ? round(($completed / $totalAssessments) * 100, 2)
              : 0.0,
        ];
    }
}
