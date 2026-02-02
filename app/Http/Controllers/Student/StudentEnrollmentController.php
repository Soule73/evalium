<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\Admin\EnrollmentService;
use App\Services\Core\GradeCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
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

        $currentEnrollment = $this->enrollmentService->getCurrentEnrollment($student);

        if (! $currentEnrollment) {
            return Inertia::render('Student/Enrollment/NoEnrollment');
        }

        $currentEnrollment->load([
            'class.academicYear',
            'class.level',
            'class.classSubjects.subject',
            'class.classSubjects.teacher',
            'class.classSubjects.assessments',
        ]);

        $gradeBreakdown = $this->gradeCalculationService->getGradeBreakdown(
            $student,
            $currentEnrollment->class
        );

        return Inertia::render('Student/Enrollment/Show', [
            'enrollment' => $currentEnrollment,
            'gradeBreakdown' => $gradeBreakdown,
        ]);
    }

    /**
     * Display enrollment history.
     */
    public function history(Request $request): Response
    {
        $student = $request->user();

        $enrollments = Enrollment::where('student_id', $student->id)
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

        $currentEnrollment = $this->enrollmentService->getCurrentEnrollment($student);

        if (! $currentEnrollment) {
            return Inertia::render('Student/Enrollment/NoEnrollment');
        }

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
