<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherDashboardService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassSubjectController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly TeacherDashboardService $dashboardService
    ) {}

    /**
     * Display all active class-subject assignments for the authenticated teacher.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);

        $classSubjects = $this->dashboardService->getActiveAssignments(
            $teacherId,
            $selectedYearId,
            $search,
            $perPage
        );

        return Inertia::render('Teacher/ClassSubjects/Index', [
            'classSubjects' => $classSubjects->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }
}
