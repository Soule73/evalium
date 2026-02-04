<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Services\Admin\EnrollmentService;
use App\Services\Core\GradeCalculationService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly GradeCalculationService $gradeCalculationService
    ) {}

    /**
     * Display the student's current enrollment.
     */
    public function show(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $currentEnrollment = $this->enrollmentService->getCurrentEnrollment($student, $selectedYearId);

        if (! $currentEnrollment) {
            return Inertia::render('Student/Enrollment/NoEnrollment');
        }

        $currentEnrollment->load([
            'class.academicYear',
            'class.level',
        ]);

        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');

        $subjectsQuery = ClassSubject::active()
            ->where('class_id', $currentEnrollment->class_id)
            ->with(['subject', 'teacher'])
            ->when($search, function ($query, $search) {
                $query->whereHas('subject', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('teacher', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });

        $subjects = $subjectsQuery->paginate($perPage)->withQueryString();

        $subjects->getCollection()->transform(function ($classSubject) use ($student) {
            $subjectGrade = $this->gradeCalculationService->calculateSubjectGrade($student, $classSubject);
            $assessmentDetails = AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubject) {
                $query->where('class_subject_id', $classSubject->id);
            })
                ->where('student_id', $student->id)
                ->count();

            $completedCount = AssessmentAssignment::whereHas('assessment', function ($query) use ($classSubject) {
                $query->where('class_subject_id', $classSubject->id);
            })
                ->where('student_id', $student->id)
                ->whereNotNull('score')
                ->count();

            return [
                'id' => $classSubject->id,
                'class_subject_id' => $classSubject->id,
                'subject_name' => $classSubject->subject->name,
                'teacher_name' => $classSubject->teacher->name,
                'coefficient' => $classSubject->coefficient,
                'average' => $subjectGrade,
                'assessments_count' => $assessmentDetails,
                'completed_count' => $completedCount,
            ];
        });

        $overallStats = $this->gradeCalculationService->getGradeBreakdown(
            $student,
            $currentEnrollment->class
        );

        return Inertia::render('Student/Enrollment/Show', [
            'enrollment' => $currentEnrollment,
            'subjects' => $subjects,
            'overallStats' => $overallStats,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display enrollment history.
     */
    public function history(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $enrollments = Enrollment::where('student_id', $student->id)
            ->forAcademicYear($selectedYearId)
            ->with([
                'class.academicYear',
                'class.level',
            ])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return Inertia::render('Student/Enrollment/History', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Display classmates.
     */
    public function classmates(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $currentEnrollment = $this->enrollmentService->getCurrentEnrollment($student, $selectedYearId);

        if (! $currentEnrollment) {
            return Inertia::render('Student/Enrollment/NoEnrollment');
        }

        $this->validateAcademicYearAccess($currentEnrollment, $selectedYearId);

        $classmates = Enrollment::where('class_id', $currentEnrollment->class_id)
            ->where('student_id', '!=', $student->id)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->pluck('student');

        $currentEnrollment->load([
            'class.academicYear',
            'class.level',
        ]);

        return Inertia::render('Student/Enrollment/Classmates', [
            'enrollment' => $currentEnrollment,
            'classmates' => $classmates,
        ]);
    }
}
