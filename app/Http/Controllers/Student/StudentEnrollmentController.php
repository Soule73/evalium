<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\SubjectStatsResource;
use App\Services\Admin\EnrollmentService;
use App\Services\Core\GradeCalculationService;
use App\Services\Student\StudentEnrollmentQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function show(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $currentEnrollment = $this->enrollmentService->getCurrentEnrollment($student, $selectedYearId);

        if (! $currentEnrollment) {
            return redirect()->route('dashboard')->with('info', 'No active enrollment found for the selected academic year.');
        }

        $filters = ['search' => $request->input('search')];
        $perPage = (int) $request->input('per_page', 10);

        $allSubjects = $this->enrollmentQueryService->getAllSubjectsWithStats(
            $currentEnrollment,
            $student,
            $filters
        );

        $page = (int) $request->input('page', 1);
        $paginatedSubjects = new LengthAwarePaginator(
            $allSubjects->forPage($page, $perPage)->values(),
            $allSubjects->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $subjectsTransformed = $paginatedSubjects->through(function ($classSubject) use ($student) {
            return (new SubjectStatsResource($classSubject))->forStudent($student)->resolve();
        });

        $overallStats = $this->gradeCalculationService->getGradeBreakdownFromLoaded(
            $student,
            $currentEnrollment->class,
            $allSubjects
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

        $classmates = $this->enrollmentQueryService->getClassmates($currentEnrollment, $student);

        return Inertia::render('Student/Enrollment/Classmates', [
            'enrollment' => $currentEnrollment,
            'classmates' => $classmates,
        ]);
    }
}
