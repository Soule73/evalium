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
     * Formula: Note_Matière = Σ(coefficient_assessment × score) / Σ(coefficient_assessment)
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
            $totalWeightedScore += $grade['coefficient'] * $grade['score'];
            $totalCoefficients += $grade['coefficient'];
        }

        return $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : null;
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

        return $totalCoefficients > 0 ? $totalWeightedGrade / $totalCoefficients : null;
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
            $assessmentDetails = $this->getAssessmentGrades($student, $classSubject);

            $subjectGrades[] = [
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject->name,
                'teacher_name' => $classSubject->teacher->name,
                'coefficient' => $classSubject->coefficient,
                'grade' => $subjectGrade,
                'assessments' => $assessmentDetails->toArray(),
            ];

            if ($subjectGrade !== null) {
                $totalWeightedGrade += $classSubject->coefficient * $subjectGrade;
                $totalCoefficients += $classSubject->coefficient;
            }
        }

        $annualAverage = $totalCoefficients > 0 ? $totalWeightedGrade / $totalCoefficients : null;

        return [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class_id' => $class->id,
            'description' => $class->description,
            'subjects' => $subjectGrades,
            'annual_average' => $annualAverage,
            'total_coefficient' => $totalCoefficients,
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
            ->with(['assessment'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'assessment_id' => $assignment->assessment_id,
                    'title' => $assignment->assessment->title,
                    'type' => $assignment->assessment->type,
                    'coefficient' => $assignment->assessment->coefficient,
                    'score' => $assignment->score,
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

        return $studentCount > 0 ? $totalGrade / $studentCount : null;
    }
}
