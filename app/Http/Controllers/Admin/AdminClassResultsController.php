<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Services\Teacher\TeacherClassResultsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays aggregated class results for admin (all subjects, all teachers).
 */
class AdminClassResultsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TeacherClassResultsService $resultsService
    ) {}

    /**
     * Display the class results page with global per-assessment and per-student statistics.
     */
    public function index(ClassModel $class): Response
    {
        $this->authorize('view', $class);

        $class->load(['academicYear', 'level']);

        $results = $this->resultsService->getClassResults($class);

        return Inertia::render('Admin/Classes/Results', [
            'class' => $class,
            'results' => $results,
            'chartData' => Inertia::defer(fn () => $this->resultsService->getChartData($class)),
        ]);
    }
}
