<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Services\Teacher\TeacherClassResultsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays aggregated class results for a teacher's class.
 */
class TeacherClassResultsController extends Controller
{
    public function __construct(
        private readonly TeacherClassResultsService $resultsService
    ) {}

    /**
     * Display the class results page with per-assessment and per-student statistics.
     */
    public function index(Request $request, ClassModel $class): Response
    {
        $teacherId = $request->user()->id;

        $class->load(['academicYear', 'level']);

        $results = $this->resultsService->getClassResults($class, $teacherId);

        return Inertia::render('Teacher/Classes/Results', [
            'class' => $class,
            'results' => $results,
        ]);
    }
}
