<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use App\Services\Student\StudentAssignmentQueryService;
use App\Services\Student\StudentDashboardService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly AdminDashboardService $adminDashboardService,
        private readonly StudentAssignmentQueryService $studentAssignmentQueryService,
        private readonly StudentDashboardService $studentDashboardService
    ) {}

    /**
     * Display the dashboard index page.
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {

            /** @var \App\Models\User $user */
            $user = $request->user();

            if (! $user) {
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
     */
    public function student(Request $request): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('student')) {
            abort(403, __('messages.unauthorized'));
        }

        $stats = $this->studentDashboardService->getDashboardStats($user);

        $allAssignments = $this->studentAssignmentQueryService->getAssignmentsForStudentLight($user);

        $page = $request->input('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $items = $allAssignments->slice($offset, $perPage)->values();

        $total = $allAssignments->count();

        $assessmentAssignments = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Dashboard/Student', [
            'user' => $user,
            'stats' => $stats,
            'assessmentAssignments' => $assessmentAssignments,
        ]);
    }

    /**
     * Handle the request for the teacher dashboard.
     */
    public function teacher(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('teacher.dashboard');
    }

    /**
     * Handle the admin dashboard request.
     */
    public function admin(Request $request): Response
    {
        $user = $request->user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super_admin'))) {
            abort(403, __('messages.unauthorized'));
        }

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $dashboardData = $this->adminDashboardService->getDashboardData($selectedYearId);

        return Inertia::render('Dashboard/Admin', [
            'user' => $user,
            'stats' => $dashboardData['stats'],
        ]);
    }
}
