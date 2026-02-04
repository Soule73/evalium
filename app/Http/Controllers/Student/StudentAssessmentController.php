<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
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

        $enrollment = $student->enrollments()
            ->where('status', 'active')
            ->whereHas('class', function ($query) use ($selectedYearId) {
                $query->where('academic_year_id', $selectedYearId);
            })
            ->with(['class.classSubjects'])
            ->first();

        if (! $enrollment) {
            return Inertia::render('Student/Assessments/Index', [
                'assessments' => [],
                'filters' => $filters,
            ]);
        }

        $classSubjectIds = $enrollment->class->classSubjects->pluck('id');

        $assessmentsQuery = Assessment::whereIn('class_subject_id', $classSubjectIds)
            ->with([
                'classSubject.class',
                'classSubject.subject',
                'classSubject.teacher',
                'questions',
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('scheduled_at', 'asc');

        $assessments = $assessmentsQuery->paginate($perPage)->withQueryString();

        $assignments = $this->assessmentService->getAssessmentsWithAssignments($student, $assessments->getCollection());

        $assessments->setCollection($assignments);

        if ($filters['status'] ?? null) {
            $assessments->setCollection(
                $assessments->getCollection()->filter(function ($assignment) use ($filters) {
                    return $assignment->status === $filters['status'];
                })->values()
            );
        }

        return Inertia::render('Student/Assessments/Index', [
            'assignments' => $assessments,
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

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            'You cannot access this assessment.'
        );

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
     * Start an assessment (mark started_at).
     */
    public function start(Assessment $assessment)
    {
        $student = Auth::user();

        abort_unless(
            $this->assessmentService->canStudentAccessAssessment($student, $assessment),
            403,
            'You cannot access this assessment.'
        );

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment);

        $this->assessmentService->startAssessment($assignment);

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

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment, startNow: true);

        $assignment->load([
            'assessment.classSubject.class',
            'assessment.classSubject.subject',
            'assessment.questions.choices',
            'answers',
        ]);

        if ($assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        if (! $assignment->wasRecentlyCreated && ! $assignment->started_at) {
            $this->assessmentService->startAssessment($assignment);
        }

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

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment, startNow: true);

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

        $assignment = $this->assessmentService->getOrCreateAssignment($student, $assessment, startNow: true);

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

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->with(['answers.question', 'answers.choice'])
            ->first();

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
}
