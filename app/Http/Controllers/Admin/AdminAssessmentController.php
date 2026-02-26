<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AdminAssessmentRepositoryInterface;
use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesAssessmentViewing;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\Assessment;
use App\Repositories\Teacher\GradingRepository;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Services\Core\AssessmentService;
use App\Services\Core\AssessmentStatsService;
use App\Services\Core\Scoring\ScoringService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin Assessment Controller
 *
 * Provides assessment listing for administrators.
 * Show/review/grade/saveGrade are handled by HandlesAssessmentViewing trait.
 */
class AdminAssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesAssessmentViewing, HandlesIndexRequests;

    public function __construct(
        private readonly AdminAssessmentRepositoryInterface $assessmentQueryService,
        private readonly TeacherAssessmentRepositoryInterface $teacherAssessmentQueryService,
        private readonly AssessmentService $assessmentService,
        private readonly GradingRepository $gradingQueryService,
        private readonly AnswerFormatterService $answerFormatterService,
        private readonly ScoringService $scoringService,
        private readonly AssessmentStatsService $assessmentStatsService
    ) {}

    protected function resolveAssessmentQueryService(): TeacherAssessmentRepositoryInterface
    {
        return $this->teacherAssessmentQueryService;
    }

    protected function resolveAssessmentService(): AssessmentService
    {
        return $this->assessmentService;
    }

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

        $filterData = $this->assessmentQueryService->getFilterData($selectedYearId);

        return Inertia::render('Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $filters,
            'classes' => $filterData['classes'],
            'subjects' => $filterData['subjects'],
            'teachers' => $filterData['teachers'],
            'routeContext' => $this->buildRouteContext(),
        ]);
    }

    /**
     * Build route context array for admin role.
     *
     * @return array<string, string|null>
     */
    protected function buildRouteContext(): array
    {
        return [
            'role' => 'admin',
            'backRoute' => 'admin.assessments.index',
            'showRoute' => null,
            'classAssessmentShowRoute' => 'admin.classes.assessments.show',
            'reviewRoute' => 'admin.assessments.review',
            'gradeRoute' => 'admin.assessments.grade',
            'saveGradeRoute' => 'admin.assessments.saveGrade',
            'editRoute' => null,
            'publishRoute' => null,
            'unpublishRoute' => null,
            'duplicateRoute' => null,
            'reopenRoute' => null,
            'createRoute' => null,
        ];
    }
}
