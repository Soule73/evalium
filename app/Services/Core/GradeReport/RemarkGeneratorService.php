<?php

namespace App\Services\Core\GradeReport;

/**
 * Auto-generates textual remarks based on grade values.
 *
 * Grade ranges map to localized remark strings.
 * Teachers may override auto-generated remarks per student per subject.
 */
class RemarkGeneratorService
{
    /**
     * @var array<int, string>
     */
    private const GRADE_THRESHOLDS = [
        16 => 'remark_excellent',
        14 => 'remark_good',
        12 => 'remark_fairly_good',
        10 => 'remark_satisfactory',
        0 => 'remark_insufficient',
    ];

    /**
     * Generate a remark string for a given grade.
     */
    public function forGrade(?float $grade): string
    {
        if ($grade === null) {
            return __('messages.remark_no_grade');
        }

        foreach (self::GRADE_THRESHOLDS as $threshold => $key) {
            if ($grade >= $threshold) {
                return __("messages.{$key}");
            }
        }

        return __('messages.remark_insufficient');
    }

    /**
     * Generate remarks for an array of subject grades.
     *
     * @param  array<int, array{class_subject_id: int, subject_name: string, grade: float|null}>  $subjects
     * @return array<int, array{class_subject_id: int, subject_name: string, remark: string, auto_generated: bool}>
     */
    public function forSubjects(array $subjects): array
    {
        return array_map(fn (array $subject) => [
            'class_subject_id' => $subject['class_subject_id'],
            'subject_name' => $subject['subject_name'],
            'remark' => $this->forGrade($subject['grade'] ?? null),
            'auto_generated' => true,
        ], $subjects);
    }

    /**
     * Generate a general remark based on the overall average.
     */
    public function forOverallAverage(?float $average): string
    {
        return $this->forGrade($average);
    }
}
