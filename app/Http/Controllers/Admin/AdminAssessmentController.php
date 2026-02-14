<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesIndexRequests;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use App\Services\Admin\AdminAssessmentQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin Assessment Controller
 *
 * Provides read-only assessment consultation for administrators.
 * Delegates all query logic to AdminAssessmentQueryService.
 */
class AdminAssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests, HasFlashMessages;

    public function __construct(
        private readonly AdminAssessmentQueryService $assessmentQueryService
    ) {}

    /**
     * Display a listing of all assessments across the platform.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessment::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'class_id', 'subject_id', 'teacher_id', 'type', 'delivery_mode']
        );

        $assessments = $this->assessmentQueryService->getAllAssessments(
            $selectedYearId,
            $filters,
            $perPage
        );

        $classes = ClassModel::query()
            ->when($selectedYearId, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::orderBy('name')->get(['id', 'name']);

        $teachers = User::role('teacher')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $filters,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }
}
