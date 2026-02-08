<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\SubjectStatsResource;
use App\Services\Admin\EnrollmentService;
use App\Services\Core\GradeCalculationService;
use App\Services\Student\StudentEnrollmentQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly StudentEnrollmentQueryService $enrollmentQueryService,
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

        $filters = ['search' => $request->input('search')];
        $perPage = (int) $request->input('per_page', 10);

        $subjects = $this->enrollmentQueryService->getSubjectsWithStatsForEnrollment(
            $currentEnrollment,
            $student,
            $filters,
            $perPage
        );

        SubjectStatsResource::setGradeService($this->gradeCalculationService);

        $subjectsTransformed = $subjects->through(function ($classSubject) use ($student) {
            return (new SubjectStatsResource($classSubject))->forStudent($student)->resolve();
        });

        $overallStats = $this->gradeCalculationService->getGradeBreakdown(
            $student,
            $currentEnrollment->class
        );

        return Inertia::render('Student/Enrollment/Show', [
            'enrollment' => $currentEnrollment,
            'subjects' => $subjectsTransformed,
            'overallStats' => $overallStats,
            'filters' => $filters,
        ]);
    }

    /**
     * Display enrollment history.
     */
    public function history(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $enrollments = $this->enrollmentQueryService->getEnrollmentHistory($student, $selectedYearId);

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

        $this->enrollmentQueryService->validateAcademicYearAccess($currentEnrollment, $selectedYearId);

        $currentEnrollment->load([
            'class.academicYear',
            'class.level',
        ]);

        $classmates = $this->enrollmentQueryService->getClassmates($currentEnrollment, $student);

        return Inertia::render('Student/Enrollment/Classmates', [
            'enrollment' => $currentEnrollment,
            'classmates' => $classmates,
        ]);
    }
}
