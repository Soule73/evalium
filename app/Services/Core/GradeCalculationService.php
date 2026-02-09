<?php

namespace App\Services\Core;

use App\Models\AcademicYear;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Grade Calculation Service - Implement double coefficient formula
 *
 * Single Responsibility: Calculate grades using:
 * - Note_Matière = Σ(coef_assessment × score) / Σ(coef_assessment)
 * - Moyenne_Annuelle = Σ(coef_subject × note_matière) / Σ(coef_subject)
 */
class GradeCalculationService
{
    /**
     * Calculate final grade for a student in a specific subject (class-subject)
     *
     * Formula: Note_Matière = Σ(coefficient_assessment × note_normalisée) / Σ(coefficient_assessment)
     * Where note_normalisée = (score / max_points) × 20
     */
    public function calculateSubjectGrade(User $student, ClassSubject $classSubject): ?float
    {
        $assessmentGrades = $this->getAssessmentGrades($student, $classSubject);

        if ($assessmentGrades->isEmpty()) {
            return null;
        }

        $totalWeightedScore = 0;
        $totalCoefficients = 0;

        foreach ($assessmentGrades as $grade) {
            if ($grade['max_points'] > 0) {
                $normalizedScore = ($grade['score'] / $grade['max_points']) * 20;
                $totalWeightedScore += $grade['coefficient'] * $normalizedScore;
                $totalCoefficients += $grade['coefficient'];
            }
        }

        return $totalCoefficients > 0 ? round($totalWeightedScore / $totalCoefficients, 2) : null;
    }

    /**
     * Calculate annual average for a student across all subjects
     *
     * Formula: Moyenne_Annuelle = Σ(coefficient_subject × note_matière) / Σ(coefficient_subject)
     */
    public function calculateAnnualAverage(User $student, AcademicYear $academicYear): ?float
    {
        $subjectGrades = $this->getSubjectGrades($student, $academicYear);

        if ($subjectGrades->isEmpty()) {
            return null;
        }

        $totalWeightedGrade = 0;
        $totalCoefficients = 0;

        foreach ($subjectGrades as $grade) {
            if ($grade['grade'] !== null) {
                $totalWeightedGrade += $grade['coefficient'] * $grade['grade'];
                $totalCoefficients += $grade['coefficient'];
            }
        }

        return $totalCoefficients > 0 ? round($totalWeightedGrade / $totalCoefficients, 2) : null;
    }

    /**
     * Get detailed grade breakdown for a student in a class
     */
    public function getGradeBreakdown(User $student, ClassModel $class): array
    {
        $classSubjects = ClassSubject::active()
            ->where('class_id', $class->id)
            ->with(['subject', 'teacher'])
            ->get();

        $subjectGrades = [];
        $totalWeightedGrade = 0;
        $totalCoefficients = 0;

        foreach ($classSubjects as $classSubject) {
            $subjectGrade = $this->calculateSubjectGrade($student, $classSubject);
            $assessmentStats = $this->getAssessmentStatsForStudent($student, $classSubject);

            $subjectGrades[] = [
                'id' => $classSubject->id,
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject?->name ?? '-',
                'teacher_name' => $classSubject->teacher?->name ?? '-',
                'coefficient' => $classSubject->coefficient,
                'average' => $subjectGrade,
                'assessments_count' => $assessmentStats['total'],
                'completed_count' => $assessmentStats['completed'],
            ];

            if ($subjectGrade !== null) {
                $totalWeightedGrade += $classSubject->coefficient * $subjectGrade;
                $totalCoefficients += $classSubject->coefficient;
            }
        }

        $annualAverage = $totalCoefficients > 0 ? round($totalWeightedGrade / $totalCoefficients, 2) : null;

        return [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class_id' => $class->id,
            'class_name' => $class->name,
            'subjects' => $subjectGrades,
            'annual_average' => $annualAverage,
            'total_coefficient' => $totalCoefficients,
        ];
    }

    /**
     * Get assessment statistics for a student in a class-subject
     *
     * @return array{total: int, completed: int}
     */
    private function getAssessmentStatsForStudent(User $student, ClassSubject $classSubject): array
    {
        $totalAssessments = $classSubject->assessments()
            ->whereRaw("JSON_EXTRACT(settings, '$.is_published') = true")
            ->count();

        $completedAssessments = AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubject) {
            $query->where('class_subject_id', $classSubject->id)
                ->whereRaw("JSON_EXTRACT(settings, '$.is_published') = true");
        })
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->count();

        return [
            'total' => $totalAssessments,
            'completed' => $completedAssessments,
        ];
    }

    /**
     * Get assessment grades for a student in a class-subject
     */
    private function getAssessmentGrades(User $student, ClassSubject $classSubject): Collection
    {
        return AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubject) {
            $query->where('class_subject_id', $classSubject->id);
        })
            ->where('student_id', $student->id)
            ->whereNotNull('score')
            ->with(['assessment.questions'])
            ->get()
            ->map(function ($assignment) {
                $maxPoints = $assignment->assessment->questions->sum('points');

                return [
                    'assessment_id' => $assignment->assessment_id,
                    'title' => $assignment->assessment->title,
                    'type' => $assignment->assessment->type,
                    'coefficient' => $assignment->assessment->coefficient,
                    'score' => $assignment->score,
                    'max_points' => $maxPoints,
                    'submitted_at' => $assignment->submitted_at,
                ];
            });
    }

    /**
     * Get subject grades for a student in an academic year
     */
    private function getSubjectGrades(User $student, AcademicYear $academicYear): Collection
    {
        $enrollment = $student->enrollments()
            ->whereHas('class', function ($query) use ($academicYear) {
                $query->where('academic_year_id', $academicYear->id);
            })
            ->with('class')
            ->first();

        if (! $enrollment) {
            return collect([]);
        }

        $classSubjects = ClassSubject::active()
            ->where('class_id', $enrollment->class_id)
            ->with(['subject'])
            ->get();

        return $classSubjects->map(function ($classSubject) use ($student) {
            return [
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject->name,
                'coefficient' => $classSubject->coefficient,
                'grade' => $this->calculateSubjectGrade($student, $classSubject),
            ];
        });
    }

    /**
     * Calculate class average for a specific subject
     */
    public function calculateClassAverageForSubject(ClassSubject $classSubject): ?float
    {
        $students = $classSubject->class->students;

        if ($students->isEmpty()) {
            return null;
        }

        $totalGrade = 0;
        $studentCount = 0;

        foreach ($students as $student) {
            $grade = $this->calculateSubjectGrade($student, $classSubject);
            if ($grade !== null) {
                $totalGrade += $grade;
                $studentCount++;
            }
        }

        return $studentCount > 0 ? round($totalGrade / $studentCount, 2) : null;
    }

    /**
     * Get overall statistics for a student (centralized for dashboard)
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Filter by academic year
     * @return array{overall_average: float|null, total_assessments: int, graded_assessments: int, pending_assessments: int, subjects_breakdown: array}
     */
    public function getStudentOverallStats(User $student, ?int $academicYearId = null): array
    {
        $query = $student->enrollments()->where('status', 'active');

        if ($academicYearId) {
            $query->whereHas('class', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        $enrollment = $query->with('class')->first();

        if (! $enrollment) {
            return [
                'overall_average' => null,
                'total_assessments' => 0,
                'graded_assessments' => 0,
                'pending_assessments' => 0,
                'subjects_breakdown' => [],
            ];
        }

        $gradeBreakdown = $this->getGradeBreakdown($student, $enrollment->class);

        $assignmentsQuery = AssessmentAssignment::where('student_id', $student->id);
        if ($academicYearId) {
            $assignmentsQuery->whereHas('assessment.classSubject.class', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        $totalAssessments = $assignmentsQuery->count();
        $gradedAssessments = (clone $assignmentsQuery)->whereNotNull('score')->count();
        $pendingAssessments = (clone $assignmentsQuery)->whereNull('submitted_at')->count();

        return [
            'overall_average' => $gradeBreakdown['annual_average'],
            'total_assessments' => $totalAssessments,
            'graded_assessments' => $gradedAssessments,
            'pending_assessments' => $pendingAssessments,
            'subjects_breakdown' => $gradeBreakdown['subjects'],
        ];
    }

    /**
     * Get detailed assessment summary with normalized grades
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Filter by academic year
     * @return array[] List of assessments with raw score, max points, and normalized grade on /20
     */
    public function getStudentAssessmentSummary(User $student, ?int $academicYearId = null): array
    {
        $query = AssessmentAssignment::where('student_id', $student->id)
            ->with([
                'assessment.questions',
                'assessment.classSubject.subject',
                'assessment.classSubject.class',
            ]);

        if ($academicYearId) {
            $query->whereHas('assessment.classSubject.class', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        return $query->get()->map(function ($assignment) {
            $maxPoints = $assignment->assessment->questions->sum('points');
            $normalizedGrade = null;

            if ($assignment->score !== null && $maxPoints > 0) {
                $normalizedGrade = round(($assignment->score / $maxPoints) * 20, 2);
            }

            return [
                'id' => $assignment->id,
                'assessment_id' => $assignment->assessment_id,
                'title' => $assignment->assessment->title,
                'type' => $assignment->assessment->type,
                'subject_name' => $assignment->assessment->classSubject->subject->name ?? null,
                'class_name' => $assignment->assessment->classSubject->class->name ?? null,
                'coefficient' => $assignment->assessment->coefficient,
                'raw_score' => $assignment->score,
                'max_points' => $maxPoints,
                'normalized_grade' => $normalizedGrade,
                'status' => $assignment->status,
                'submitted_at' => $assignment->submitted_at?->toISOString(),
                'graded_at' => $assignment->graded_at?->toISOString(),
            ];
        })->toArray();
    }
}
