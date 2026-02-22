<?php

namespace App\Services\Core;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $classSubjectIds = $classSubjects->pluck('id');

        $allAssignments = AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubjectIds) {
            $query->whereIn('class_subject_id', $classSubjectIds);
        })
            ->forStudent($student)
            ->withSum('answers', 'score')
            ->with(['assessment' => function ($query) {
                $query->select('id', 'title', 'type', 'coefficient', 'class_subject_id', 'is_published', 'settings')
                    ->withCount('questions');
            }, 'assessment.questions:id,assessment_id,points'])
            ->get()
            ->groupBy('assessment.class_subject_id');

        $publishedAssessmentsBySubject = DB::table('assessments')
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_published', true)
            ->whereNull('deleted_at')
            ->select('class_subject_id', DB::raw('COUNT(*) as total'))
            ->groupBy('class_subject_id')
            ->pluck('total', 'class_subject_id');

        $subjectGrades = [];
        $totalWeightedGrade = 0;
        $totalCoefficients = 0;

        foreach ($classSubjects as $classSubject) {
            $assignments = $allAssignments->get($classSubject->id, collect());

            $subjectGrade = null;
            $completedCount = 0;

            if ($assignments->isNotEmpty()) {
                $totalWeightedScore = 0;
                $totalAssessmentCoefficients = 0;

                foreach ($assignments as $assignment) {
                    if ($assignment->score !== null && $assignment->assessment) {
                        $maxPoints = $assignment->assessment->questions->sum('points');
                        if ($maxPoints > 0) {
                            $normalizedScore = ($assignment->score / $maxPoints) * 20;
                            $totalWeightedScore += $assignment->assessment->coefficient * $normalizedScore;
                            $totalAssessmentCoefficients += $assignment->assessment->coefficient;
                        }
                    }

                    if ($assignment->submitted_at) {
                        $completedCount++;
                    }
                }

                if ($totalAssessmentCoefficients > 0) {
                    $subjectGrade = round($totalWeightedScore / $totalAssessmentCoefficients, 2);
                }
            }

            $totalAssessments = $publishedAssessmentsBySubject->get($classSubject->id, 0);

            $subjectGrades[] = [
                'id' => $classSubject->id,
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject?->name ?? '-',
                'teacher_name' => $classSubject->teacher?->name ?? '-',
                'coefficient' => $classSubject->coefficient,
                'average' => $subjectGrade,
                'assessments_count' => $totalAssessments,
                'completed_count' => $completedCount,
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
     * Compute grade breakdown from pre-loaded class subjects (zero additional queries).
     *
     * Expects classSubjects to have eager-loaded: subject, teacher,
     * assessments (with settings), assessments.questions, assessments.assignments (filtered by student).
     *
     * @param  User  $student  The student
     * @param  ClassModel  $class  The class
     * @param  Collection  $classSubjects  Pre-loaded class subjects with all relationships
     * @return array Grade breakdown data
     */
    public function getGradeBreakdownFromLoaded(User $student, ClassModel $class, Collection $classSubjects): array
    {
        $subjectGrades = [];
        $totalWeightedGrade = 0;
        $totalCoefficients = 0;

        foreach ($classSubjects as $classSubject) {
            $subjectGrade = null;
            $completedCount = 0;
            $totalWeightedScore = 0;
            $totalAssessmentCoefficients = 0;

            foreach ($classSubject->assessments as $assessment) {
                $assignment = $assessment->assignments->first();

                if ($assignment) {
                    if ($assignment->score !== null) {
                        $maxPoints = $assessment->questions->sum('points');

                        if ($maxPoints > 0) {
                            $normalizedScore = ($assignment->score / $maxPoints) * 20;
                            $totalWeightedScore += $assessment->coefficient * $normalizedScore;
                            $totalAssessmentCoefficients += $assessment->coefficient;
                        }
                    }

                    if ($assignment->submitted_at) {
                        $completedCount++;
                    }
                }
            }

            if ($totalAssessmentCoefficients > 0) {
                $subjectGrade = round($totalWeightedScore / $totalAssessmentCoefficients, 2);
            }

            $totalAssessments = $classSubject->assessments
                ->filter(fn ($a) => $a->is_published)
                ->count();

            $subjectGrades[] = [
                'id' => $classSubject->id,
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject?->name ?? '-',
                'teacher_name' => $classSubject->teacher?->name ?? '-',
                'coefficient' => $classSubject->coefficient,
                'average' => $subjectGrade,
                'assessments_count' => $totalAssessments,
                'completed_count' => $completedCount,
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
     * Get assessment grades for a student in a class-subject
     */
    private function getAssessmentGrades(User $student, ClassSubject $classSubject): Collection
    {
        return AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubject) {
            $query->where('class_subject_id', $classSubject->id);
        })
            ->forStudent($student)
            ->whereNotNull('graded_at')
            ->withSum('answers', 'score')
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
     * Get overall statistics for a student (centralized for dashboard - optimized version)
     *
     * @param  User  $student  The student
     * @param  int|null  $academicYearId  Filter by academic year
     * @param  \App\Models\Enrollment|null  $enrollment  Pre-loaded enrollment to avoid duplicate query
     * @return array{overall_average: float|null, total_assessments: int, graded_assessments: int, pending_assessments: int, subjects_breakdown: array}
     */
    public function getStudentOverallStats(User $student, ?int $academicYearId = null, $enrollment = null): array
    {
        if (! $enrollment) {
            $query = $student->enrollments()->where('status', 'active');

            if ($academicYearId) {
                $query->whereHas('class', function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                });
            }

            $enrollment = $query->with('class')->first();
        }

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

        $stats = DB::table('assessments as a')
            ->join('class_subjects as cs', 'cs.id', '=', 'a.class_subject_id')
            ->leftJoin('assessment_assignments as aa', function ($join) use ($enrollment) {
                $join->on('aa.assessment_id', '=', 'a.id')
                    ->where('aa.enrollment_id', '=', $enrollment->id);
            })
            ->where('cs.class_id', $enrollment->class_id)
            ->where('a.is_published', true)
            ->whereNull('a.deleted_at')
            ->selectRaw('
                COUNT(*) as total_assessments,
                SUM(CASE WHEN aa.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded_assessments,
                SUM(CASE WHEN aa.submitted_at IS NULL THEN 1 ELSE 0 END) as pending_assessments
            ')
            ->first();

        return [
            'overall_average' => $gradeBreakdown['annual_average'],
            'total_assessments' => (int) ($stats->total_assessments ?? 0),
            'graded_assessments' => (int) ($stats->graded_assessments ?? 0),
            'pending_assessments' => (int) ($stats->pending_assessments ?? 0),
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
        $query = AssessmentAssignment::forStudent($student)
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

        return $query->withSum('answers', 'score')->get()->map(function ($assignment) {
            $maxPoints = $assignment->assessment->questions->sum('points');
            $normalizedGrade = null;

            if ($assignment->graded_at !== null && $maxPoints > 0) {
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

    /**
     * Get paginated assignments for an enrollment with optional filters.
     *
     * Queries all assessments from the enrollment's class subjects (not just
     * existing assignments), creating virtual AssessmentAssignment objects
     * for assessments that haven't been started yet.
     *
     * @param  Enrollment  $enrollment  The enrollment to query
     * @param  array  $filters  Optional filters (search, class_subject_id, status)
     * @param  int  $perPage  Items per page
     * @return LengthAwarePaginator Paginated assignment results
     */
    public function getEnrollmentAssignments(Enrollment $enrollment, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $enrollment->loadMissing('class.classSubjects');

        $classSubjectIds = $enrollment->class->classSubjects->pluck('id');

        $assessmentsQuery = Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->with([
                'questions:id,assessment_id,points',
                'classSubject.subject',
                'classSubject.teacher',
            ])
            ->when(! empty($filters['class_subject_id']), function ($query) use ($filters) {
                $query->where('class_subject_id', $filters['class_subject_id']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $query->where('title', 'like', "%{$filters['search']}%");
            })
            ->latest('created_at');

        $paginator = $assessmentsQuery->paginate($perPage)->withQueryString();

        $assessmentItems = collect($paginator->items());

        $existingAssignments = AssessmentAssignment::whereIn('assessment_id', $assessmentItems->pluck('id'))
            ->where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('assessment_id');

        $assignments = $assessmentItems->map(function (Assessment $assessment) use ($enrollment, $existingAssignments) {
            $assignment = $existingAssignments->get($assessment->id);

            if (! $assignment) {
                $assignment = new AssessmentAssignment([
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $enrollment->id,
                ]);
            }

            $assignment->assessment = $assessment;

            return $assignment;
        });

        if (! empty($filters['status'])) {
            $assignments = $assignments->filter(function (AssessmentAssignment $assignment) use ($filters) {
                return $assignment->status === $filters['status'];
            })->values();
        }

        $paginator->setCollection($assignments);

        return $paginator;
    }
}
