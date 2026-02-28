<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminDashboardService;
use App\Services\Core\RoleBasedRedirectService;
use App\Services\Student\StudentAssessmentService;
use App\Services\Student\StudentDashboardService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly AdminDashboardService $adminDashboardService,
        private readonly StudentAssessmentService $studentAssessmentService,
        private readonly StudentDashboardService $studentDashboardService,
        private readonly RoleBasedRedirectService $redirectService
    ) {}

    /**
     * Display the appropriate dashboard based on user role.
     */
    public function index(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, __('messages.unauthenticated'));
        }

        if ($this->redirectService->isTeacher($user)) {
            return redirect()->route('teacher.dashboard');
        }

        if ($this->redirectService->isAdmin($user)) {
            return $this->renderAdminDashboard($request, $user);
        }

        if ($this->redirectService->isStudent($user)) {
            return $this->renderStudentDashboard($request, $user);
        }

        throw new \RuntimeException(__('messages.user_has_no_valid_role'));
    }

    /**
     * Render the student dashboard.
     */
    private function renderStudentDashboard(Request $request, $user): Response
    {
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $enrollment = $user->enrollments()
            ->where('status', 'active')
            ->when($selectedYearId, function ($query) use ($selectedYearId) {
                $query->whereHas('class', function ($q) use ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                });
            })
            ->with(['class.classSubjects'])
            ->first();

        $stats = $this->studentDashboardService->getDashboardStats($user, $selectedYearId, $enrollment);

        $filters = $request->only(['status', 'search']);
        $perPage = 3;

        $assessmentResult = $this->studentAssessmentService->getStudentAssessmentsForIndex(
            $user,
            $selectedYearId,
            $filters,
            $perPage,
            $enrollment
        );

        return Inertia::render('Dashboard/Student', [
            'user' => $user,
            'stats' => $stats,
            'assessmentAssignments' => $assessmentResult['assignments'] ?? [],
        ]);
    }

    /**
     * Render the admin dashboard.
     */
    private function renderAdminDashboard(Request $request, $user): Response
    {
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $dashboardData = $this->adminDashboardService->getDashboardData($selectedYearId);

        return Inertia::render('Dashboard/Admin', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
            'chartData' => Inertia::defer(fn () => $this->adminDashboardService->getChartData($selectedYearId)),
        ]);
    }
}
