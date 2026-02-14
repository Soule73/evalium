<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\SaveManualGradeRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use App\Services\Admin\AdminAssessmentQueryService;
use App\Services\Core\Answer\AnswerFormatterService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Teacher\GradingQueryService;
use App\Services\Teacher\TeacherAssessmentQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin Assessment Controller
 *
 * Provides assessment consultation and grading for administrators.
 * Delegates query logic to services and reuses shared pages via routeContext.
 */
class AdminAssessmentController extends Controller
{
  use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests, HasFlashMessages;

  public function __construct(
    private readonly AdminAssessmentQueryService $assessmentQueryService,
    private readonly TeacherAssessmentQueryService $teacherAssessmentQueryService,
    private readonly GradingQueryService $gradingQueryService,
    private readonly AnswerFormatterService $answerFormatterService,
    private readonly ScoringService $scoringService
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
      ->when($selectedYearId, fn($q, $id) => $q->where('academic_year_id', $id))
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

  /**
   * Display the specified assessment with assignments listing.
   */
  public function show(Request $request, Assessment $assessment): Response
  {
    $this->authorize('view', $assessment);
    $perPage = (int) $request->input('per_page', 10);

    $assessment = $this->teacherAssessmentQueryService->loadAssessmentDetails($assessment);

    $assignments = $this->gradingQueryService->getAssignmentsWithEnrolledStudents(
      $assessment,
      $request->only(['search']),
      $perPage
    );

    return Inertia::render('Assessments/Show', [
      'assessment' => $assessment,
      'assignments' => $assignments,
      'routeContext' => $this->buildRouteContext(),
    ]);
  }

  /**
   * Display the review interface for a graded assignment.
   */
  public function review(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
  {
    $this->authorize('view', $assessment);
    abort_unless($assignment->assessment_id === $assessment->id, 404);

    $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
    $assignment->load(['student', 'answers.choice']);
    $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

    return Inertia::render('Assessments/Review', [
      'assignment' => $assignment,
      'assessment' => $assessment,
      'student' => $assignment->student,
      'userAnswers' => $userAnswers,
      'routeContext' => $this->buildRouteContext(),
    ]);
  }

  /**
   * Display the grading interface for a specific student assignment.
   */
  public function grade(Request $request, Assessment $assessment, AssessmentAssignment $assignment): Response
  {
    $this->authorize('update', $assessment);
    abort_unless($assignment->assessment_id === $assessment->id, 404);

    $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
    $assignment->load(['student', 'answers.choice']);
    $userAnswers = $this->answerFormatterService->formatForGrading($assignment);

    return Inertia::render('Assessments/Grade', [
      'assignment' => $assignment,
      'assessment' => $assessment,
      'student' => $assignment->student,
      'userAnswers' => $userAnswers,
      'routeContext' => $this->buildRouteContext(),
    ]);
  }

  /**
   * Save the grading for a specific student assignment.
   */
  public function saveGrade(SaveManualGradeRequest $request, Assessment $assessment, AssessmentAssignment $assignment): RedirectResponse
  {
    abort_unless($assignment->assessment_id === $assessment->id, 404);

    $this->scoringService->saveManualGrades(
      $assignment,
      $request->input('scores', []),
      $request->input('teacher_notes')
    );

    return back()->flashSuccess(__('messages.grade_saved'));
  }

  /**
   * Build route context array for admin role.
   *
   * @return array<string, string|null>
   */
  private function buildRouteContext(): array
  {
    return [
      'role' => 'admin',
      'backRoute' => 'admin.assessments.index',
      'showRoute' => 'admin.assessments.show',
      'reviewRoute' => 'admin.assessments.review',
      'gradeRoute' => 'admin.assessments.grade',
      'saveGradeRoute' => 'admin.assessments.saveGrade',
      'editRoute' => null,
      'publishRoute' => null,
      'unpublishRoute' => null,
      'duplicateRoute' => null,
      'reopenRoute' => null,
    ];
  }
}
