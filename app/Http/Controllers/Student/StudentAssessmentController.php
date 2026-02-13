<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\Student\StudentAssessmentService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StudentAssessmentController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly StudentAssessmentService $assessmentService
    ) {}

    /**
     * Display a listing of student's assessments.
     */
    public function index(Request $request): Response
    {
        $student = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $filters = $request->only(['status', 'search']);
        $perPage = $request->input('per_page', 15);

        $assignments = $this->assessmentService->getStudentAssessmentsForIndex(
            $student,
            $selectedYearId,
            $filters,
            $perPage
        );

        return Inertia::render('Student/Assessments/Index', [
            'assignments' => $assignments,
            'filters' => $filters,
        ]);
    }

    /**
     * Display the specified assessment for the student.
     */
    public function show(Assessment $assessment): Response
    {
        $student = Auth::user();

        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        return Inertia::render('Student/Assessments/Show', [
            'assignment' => $assignment,
            'assessment' => $assessment,
        ]);
    }

    /**
     * Start an assessment.
     */
    public function start(Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            'You cannot access this assessment.'
        );

        $availability = $assessment->getAvailabilityStatus();

        if (! $availability['available']) {
            return back()->with('error', __('messages.' . $availability['reason']));
        }

        return redirect()->route('student.assessments.take', $assessment);
    }

    /**
     * Take/work on an assessment.
     */
    public function take(Assessment $assessment): Response|RedirectResponse
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            'You cannot access this assessment.'
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        $availability = $assessment->getAvailabilityStatus();

        if (! $availability['available']) {
            return redirect()
                ->route('student.assessments.show', $assessment)
                ->with('error', __('messages.' . $availability['reason']));
        }

        $assignment->load([
            'assessment.classSubject.class',
            'assessment.classSubject.subject',
            'assessment.questions.choices',
            'answers',
        ]);

        return Inertia::render('Student/Assessments/Take', [
            'assignment' => $assignment,
            'assessment' => $assessment->load(['questions.choices']),
            'questions' => $assessment->questions,
            'userAnswers' => $assignment->answers,
        ]);
    }

    /**
     * Save answers (auto-save during assessment).
     */
    public function saveAnswers(Request $request, Assessment $assessment)
    {
        $student = Auth::user();

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => 'Assessment already submitted'], 400);
        }

        $this->assessmentService->saveAnswers($assignment, $request->input('answers', []));

        return response()->json(['message' => 'Answers saved successfully']);
    }

    /**
     * Submit answers for an assessment.
     */
    public function submit(Request $request, Assessment $assessment)
    {
        $student = Auth::user();

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return back()->with('error', __('messages.assessment_already_submitted'));
        }

        $this->assessmentService->submitAssessment($assignment, $assessment, $request->input('answers', []));

        return redirect()
            ->route('student.assessments.results', $assessment)
            ->with('success', __('messages.assessment_submitted'));
    }

    /**
     * Display assessment results.
     */
    public function results(Assessment $assessment): Response|RedirectResponse
    {
        $student = Auth::user();

        $assessment->load([
            'classSubject.class',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
        ]);

        $assignment = $this->assessmentService->getAssignmentForResults($student, $assessment);

        if (! $assignment || ! $assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        $userAnswers = $this->assessmentService->formatUserAnswers($assignment->answers);

        return Inertia::render('Student/Assessments/Results', [
            'assignment' => $assignment,
            'assessment' => $assessment,
            'userAnswers' => $userAnswers,
        ]);
    }

    /**
     * Handle security violation during assessment.
     */
    public function securityViolation(Request $request, Assessment $assessment)
    {
        $student = Auth::user();

        $request->validate([
            'violation_type' => ['required', 'string'],
            'violation_details' => ['nullable', 'string'],
            'answers' => ['nullable', 'array'],
        ]);

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        if ($assignment->submitted_at) {
            return response()->json(['message' => 'Assessment already submitted'], 400);
        }

        if ($request->has('answers')) {
            $this->assessmentService->saveAnswers($assignment, $request->input('answers', []));
        }

        $this->assessmentService->terminateForViolation(
            $assignment,
            $assessment,
            $request->input('violation_type'),
            $request->input('violation_details')
        );

        return response()->json(['message' => 'Security violation recorded']);
    }
}
