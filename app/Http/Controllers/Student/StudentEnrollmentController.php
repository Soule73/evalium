<?php

namespace App\Http\Controllers\Student;

use App\Contracts\Repositories\StudentEnrollmentRepositoryInterface;
use App\Contracts\Services\EnrollmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Services\Core\GradeCalculationService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
        private readonly StudentEnrollmentRepositoryInterface $enrollmentQueryService,
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
            return redirect()->route('dashboard')->flashInfo(__('messages.no_active_enrollment_for_year'));
        }

        $filters = ['search' => $request->input('search')];
        $perPage = (int) $request->input('per_page', 10);

        $allSubjects = $this->enrollmentQueryService->getAllSubjectsWithStats(
            $currentEnrollment,
            $student,
            $filters
        );

        $gradeBreakdown = $this->gradeCalculationService->getGradeBreakdownFromLoaded(
            $student,
            $currentEnrollment->class,
            $allSubjects
        );

        $page = (int) $request->input('page', 1);
        $subjectItems = collect($gradeBreakdown['subjects']);

        $paginatedSubjects = new LengthAwarePaginator(
            $subjectItems->forPage($page, $perPage)->values(),
            $subjectItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $overallStats = collect($gradeBreakdown)->except('subjects')->all();
        $overallStats['total_assessments'] = $subjectItems->sum('assessments_count');
        $overallStats['completed_assessments'] = $subjectItems->sum('completed_count');

        return Inertia::render('Student/Enrollment/Show', [
            'enrollment' => $currentEnrollment,
            'subjects' => $paginatedSubjects,
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
