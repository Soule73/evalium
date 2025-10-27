<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use App\Services\Shared\DashboardService;
use App\Services\Admin\AdminDashboardService;
use App\Services\Teacher\TeacherDashboardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ExamService $examService,
        private readonly DashboardService $dashboardService,
        private readonly TeacherDashboardService $teacherDashboardService,
        private readonly AdminDashboardService $adminDashboardService
    ) {}


    /**
     * Display the dashboard index page.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects to the appropriate route.
     */
    public function index(): RedirectResponse
    {
        try {
            $route = $this->dashboardService->getDashboardRoute();
            return redirect()->route($route);
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }
    }

    /**
     * Handles the request to display the student dashboard.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\Response The response containing the student dashboard view.
     */
    public function student(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            abort(401, 'Utilisateur non authentifié');
        }

        if (!$this->dashboardService->canAccessDashboard('student', $user)) {
            abort(403, 'Accès non autorisé au dashboard étudiant');
        }

        $allAssignments = $this->examService->getAssignedExamsForStudent($user, null);

        $stats = $this->examService->getStudentDashboardStats($allAssignments);

        $examAssignments = $this->examService->getAssignedExamsForStudent($user, 10);

        return Inertia::render('Dashboard/Student', [
            'user' => $user,
            'stats' => $stats,
            'examAssignments' => $examAssignments
        ]);
    }

    /**
     * Handle the request for the teacher dashboard.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\Response The response containing the teacher dashboard data.
     */
    public function teacher(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            abort(401, 'Utilisateur non authentifié');
        }

        if (!$this->dashboardService->canAccessDashboard('teacher', $user)) {
            abort(403, 'Accès non autorisé au dashboard professeur');
        }

        $dashboardData = $this->teacherDashboardService->getDashboardData($user);

        return Inertia::render('Dashboard/Teacher', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
            'recent_exams' => $dashboardData['recent_exams'],
            'pending_reviews' => $dashboardData['pending_reviews']
        ]);
    }

    /**
     * Handle the admin dashboard request.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\Response The response to be sent back to the client.
     */
    public function admin(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            abort(401, 'Utilisateur non authentifié');
        }

        if (!$this->dashboardService->canAccessDashboard('admin', $user)) {
            abort(403, 'Accès non autorisé au dashboard administrateur');
        }

        $dashboardData = $this->adminDashboardService->getDashboardData();

        return Inertia::render('Dashboard/Admin', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
            'activity_stats' => $dashboardData['activity_stats'],
            'recent_exams' => $dashboardData['recent_exams'],
            'role_distribution' => $dashboardData['role_distribution']
        ]);
    }
}
