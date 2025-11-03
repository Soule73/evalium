<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\ExamService;
use App\Services\Shared\DashboardService;
use App\Services\Admin\AdminDashboardService;
use App\Services\Exam\TeacherDashboardService;
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
     * @return \Inertia\Response returns the appropriate dashboard view.
     */
    public function index(Request $request): Response
    {
        try {
            // $route = $this->dashboardService->getDashboardRoute();
            // return redirect()->route($route);

            /** @var \App\Models\User $user */
            $user = $request->user();

            if (!$user) {
                abort(401, 'Utilisateur non authentifié');
            }

            if (PermissionHelper::canViewAdminDashboard()) {
                return $this->admin($request, $user);
            } else if (PermissionHelper::canViewTeacherDashboard()) {
                return $this->teacher($request, $user);
            } else if (PermissionHelper::canViewStudentDashboard()) {
                return $this->student($request, $user);
            } else {
                return $this->unified($request, $user);
            }
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }
    }

    /**
     * Affiche le dashboard par default
     */
    public function unified(Request $request, User $user): Response
    {

        return Inertia::render('Dashboard/Unified', ['user' => $user]);
    }

    /**
     * Handles the request to display the student dashboard.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Inertia\Response The response containing the student dashboard view.
     */
    public function student(Request $request, User $user): Response
    {

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
     * @return \Inertia\Response The response containing the teacher dashboard data.
     */
    public function teacher(Request $request, User $user): Response
    {

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status');
        $search = $request->input('search');

        $dashboardData = $this->teacherDashboardService->getDashboardData($user, $perPage, $status, $search);

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
     * @return \Inertia\Response The response to be sent back to the client.
     */
    public function admin(Request $request, User $user): Response
    {

        $dashboardData = $this->adminDashboardService->getDashboardData();

        return Inertia::render('Dashboard/Admin', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
            // 'activity_stats' => $dashboardData['activity_stats'],
            // 'recent_exams' => $dashboardData['recent_exams'],
            // 'role_distribution' => $dashboardData['role_distribution']
        ]);
    }
}
