<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminDashboardService;
use App\Services\Core\RoleBasedRedirectService;
use App\Services\Student\StudentAssignmentQueryService;
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
        private readonly StudentAssignmentQueryService $studentAssignmentQueryService,
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
        $stats = $this->studentDashboardService->getDashboardStats($user);

        $assessmentAssignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLightPaginated(
            $user,
            $request->only(['status', 'search']),
            $request->input('per_page', 10)
        );

        return Inertia::render('Dashboard/Student', [
            'user' => $user,
            'stats' => $stats,
            'assessmentAssignments' => $assessmentAssignments,
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
        ]);
    }
}
