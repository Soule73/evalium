<?php

namespace App\Http\Resources\Student;

use App\Services\Core\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subject Statistics Resource
 *
 * Transforms ClassSubject model into subject statistics for students.
 * Uses eager-loaded relationships to avoid N+1 queries.
 *
 * @property \App\Models\ClassSubject $resource
 */
class SubjectStatsResource extends JsonResource
{
    /**
     * The student for whom to calculate statistics.
     *
     * @var \App\Models\User
     */
    private $student;

    /**
     * The grade calculation service.
     *
     * @var GradeCalculationService
     */
    private static $gradeService;

    /**
     * Set the student for statistics calculation.
     *
     * @param  \App\Models\User  $student
     * @return $this
     */
    public function forStudent($student)
    {
        $this->student = $student;

        return $this;
    }

    /**
     * Set the grade calculation service (static for collection efficiency).
     */
    public static function setGradeService(GradeCalculationService $service): void
    {
        self::$gradeService = $service;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'class_subject_id' => $this->resource->id,
            'subject_name' => $this->resource->subject?->name ?? '',
            'teacher_name' => $this->resource->teacher?->name ?? '',
            'coefficient' => $this->resource->coefficient,
            'average' => $this->calculateAverage(),
            'assessments_count' => $this->getAssessmentsCount(),
            'completed_count' => $this->getCompletedCount(),
        ];
    }

    /**
     * Calculate the average grade for this subject.
     * Uses eager-loaded assessments and assignments.
     */
    private function calculateAverage(): ?float
    {
        if (! $this->student || ! self::$gradeService) {
            return null;
        }

        return self::$gradeService->calculateSubjectGrade($this->student, $this->resource);
    }

    /**
     * Get the total number of assessments for this subject.
     * Uses eager-loaded assessments relationship.
     */
    private function getAssessmentsCount(): int
    {
        return $this->resource->assessments->count();
    }

    /**
     * Get the number of completed assessments.
     * Uses eager-loaded assessments.assignments relationship.
     */
    private function getCompletedCount(): int
    {
        return $this->resource->assessments
            ->flatMap->assignments
            ->whereNotNull('score')
            ->count();
    }
}
