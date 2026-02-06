<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\SaveManualGradeRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\User;
use App\Services\Core\GradeCalculationService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Teacher\GradingQueryService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradingController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HasFlashMessages;

    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService,
        private readonly ScoringService $scoringService,
        private readonly GradingQueryService $gradingQueryService
    ) {}

    /**
     * Display grading interface for an assessment.
     */
    public function index(Request $request, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $assessment = $this->gradingQueryService->loadAssessmentForGradingIndex($assessment);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignments = $this->gradingQueryService->getAssignmentsForGrading(
            $assessment,
            $request->input('per_page', 10)
        );

        return Inertia::render('Teacher/Grading/Index', [
            'assessment' => $assessment,
            'assignments' => $assignments,
        ]);
    }

    /**
     * Display grading interface for a specific student.
     */
    public function show(Request $request, Assessment $assessment, User $student): Response
    {
        $this->authorize('view', $assessment);
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $assessment = $this->gradingQueryService->loadAssessmentForGradingShow($assessment);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignment = $this->gradingQueryService->getAssignmentForStudent($assessment, $student);
        $userAnswers = $this->gradingQueryService->transformUserAnswers($assignment);

        return Inertia::render('Teacher/Grading/Show', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'student' => $student,
            'userAnswers' => $userAnswers,
        ]);
    }

    /**
     * Save manual grade for a specific answer or assignment.
     */
    public function save(SaveManualGradeRequest $request, Assessment $assessment, User $student): RedirectResponse
    {
        $assignment = $this->gradingQueryService->getAssignmentForStudent($assessment, $student);

        $this->scoringService->saveManualGrades(
            $assignment,
            $request->input('scores', []),
            $request->input('teacher_notes')
        );

        return back()->flashSuccess(__('messages.grade_saved'));
    }

    /**
     * Display grade breakdown for a student in a class.
     */
    public function breakdown(Request $request, User $student, $classId): Response
    {
        $class = \App\Models\ClassModel::findOrFail($classId);

        $breakdown = $this->gradeCalculationService->getGradeBreakdown($student, $class);

        return Inertia::render('Teacher/Grading/Breakdown', [
            'student' => $student,
            'class' => $class,
            'breakdown' => $breakdown,
        ]);
    }
}
