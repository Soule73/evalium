<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use App\Services\Core\ExamQueryService;
use App\Services\Exam\TeacherDashboardService;
use App\Services\Student\StudentAssignmentQueryService;
use App\Services\Student\StudentDashboardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ExamQueryService $examQueryService,
        private readonly TeacherDashboardService $teacherDashboardService,
        private readonly AdminDashboardService $adminDashboardService,
        private readonly StudentAssignmentQueryService $studentAssignmentQueryService,
        private readonly StudentDashboardService $studentDashboardService
    ) {}

    /**
     * Display the dashboard index page.
     *
     * @return \Inertia\Response returns the appropriate dashboard view.
     */
    public function index(Request $request): Response
    {
        try {

            /** @var \App\Models\User $user */
            $user = $request->user();

            if (!$user) {
                abort(401, __('messages.unauthenticated'));
            }

            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                return $this->admin($request, $user);
            } elseif ($user->hasRole('teacher')) {

                return $this->teacher($request, $user);
            } elseif ($user->hasRole('student')) {

                return $this->student($request, $user);
            } else {

                return $this->unified($request, $user);
            }
        } catch (\Exception $e) {
            Log::error('Error accessing dashboard', [
                'exception' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            abort(403, __('messages.unauthorized'));
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
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @return \Inertia\Response The response containing the student dashboard view.
     */
    public function student(Request $request, User $user): Response
    {
        $allAssignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($user);

        $stats = $this->studentDashboardService->getDashboardStats($user);

        $page = $request->input('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $items = $allAssignments->slice($offset, $perPage)->values();

        $total = $allAssignments->count();

        $examAssignments = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Dashboard/Student', [
            'user' => $user,
            'stats' => $stats,
            'examAssignments' => $examAssignments,
        ]);
    }

    /**
     * Handle the request for the teacher dashboard.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
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
            'recentExams' => $dashboardData['recentExams'],
            'pendingReviews' => $dashboardData['pendingReviews'],
        ]);
    }

    /**
     * Handle the admin dashboard request.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @return \Inertia\Response The response to be sent back to the client.
     */
    public function admin(Request $request, User $user): Response
    {

        $dashboardData = $this->adminDashboardService->getDashboardData();

        return Inertia::render('Dashboard/Admin', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
        ]);
    }
}
