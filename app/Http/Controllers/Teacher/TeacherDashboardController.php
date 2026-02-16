<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherDashboardService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly TeacherDashboardService $dashboardService
    ) {}

    /**
     * Display the teacher dashboard with overview statistics.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $search = $request->input('search');

        $activeAssignments = $this->dashboardService->getActiveAssignments($teacherId, $selectedYearId, $search);
        $pastAssessments = $this->dashboardService->getPastAssessments($teacherId, $selectedYearId, $search);
        $upcomingAssessments = $this->dashboardService->getUpcomingAssessments($teacherId, $selectedYearId, $search);

        $stats = $this->dashboardService->getDashboardStats(
            $teacherId,
            $selectedYearId,
            $pastAssessments->total(),
            $upcomingAssessments->total()
        );

        return Inertia::render('Dashboard/Teacher', [
            'activeAssignments' => $activeAssignments,
            'pastAssessments' => $pastAssessments,
            'upcomingAssessments' => $upcomingAssessments,
            'stats' => $stats,
            'filters' => ['search' => $search],
        ]);
    }
}
