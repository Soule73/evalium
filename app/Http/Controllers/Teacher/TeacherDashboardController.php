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

        $stats = $this->dashboardService->getDashboardStats($teacherId, $selectedYearId);
        $stats['overall_average'] = $this->dashboardService->getOverallAverageScore($teacherId, $selectedYearId);

        return Inertia::render('Dashboard/Teacher', [
            'stats' => $stats,
            'chartData' => Inertia::defer(fn () => $this->dashboardService->getChartData($teacherId, $selectedYearId)),
        ]);
    }
}
