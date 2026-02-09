<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Dashboard Statistics Resource
 *
 * Ensures consistent structure for dashboard stats across the application.
 * All dashboard data should use this resource to maintain uniformity.
 */
class StudentDashboardStatsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'totalAssessments' => $this->resource['totalAssessments'] ?? 0,
      'completedAssessments' => $this->resource['completedAssessments'] ?? 0,
      'pendingAssessments' => $this->resource['pendingAssessments'] ?? 0,
      'averageScore' => $this->formatScore($this->resource['averageScore'] ?? null),
      'upcomingAssessments' => $this->resource['upcomingAssessments'] ?? [],
      'recentAssessments' => $this->resource['recentAssessments'] ?? [],
      'subjectsBreakdown' => $this->resource['subjectsBreakdown'] ?? [],
    ];
  }

  /**
   * Format score for consistent display
   */
  private function formatScore(?float $score): ?float
  {
    return $score !== null ? round($score, 2) : null;
  }
}
