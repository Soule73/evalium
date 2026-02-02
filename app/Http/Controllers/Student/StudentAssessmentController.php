<?php

namespace App\Http\Controllers\Student;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentAssessmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of student's assessments.
     */
    public function index(Request $request): Response
    {
        $studentId = $request->user()->id;

        $filters = $request->only(['status', 'search']);
        $perPage = $request->input('per_page', 15);

        $assignments = AssessmentAssignment::where('student_id', $studentId)
            ->with([
                'assessment.classSubject.class',
                'assessment.classSubject.subject',
                'assessment.questions',
            ])
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'not_started') {
                    return $query->whereNull('started_at');
                } elseif ($status === 'in_progress') {
                    return $query->whereNotNull('started_at')->whereNull('submitted_at');
                } elseif ($status === 'completed') {
                    return $query->whereNotNull('submitted_at');
                }

                return $query;
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->whereHas('assessment', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            })
            ->orderBy('assigned_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

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
        $studentId = Auth::id();

        $this->authorize('view', $assessment);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->with([
                'assessment.classSubject.class',
                'assessment.classSubject.subject',
                'assessment.questions.choices',
                'answers.question',
            ])
            ->firstOrFail();

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
        $studentId = Auth::id();

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if (! $assignment->started_at) {
            $assignment->update(['started_at' => now()]);
        }

        return redirect()->route('student.assessments.take', $assessment);
    }

    /**
     * Take/work on an assessment.
     */
    public function take(Assessment $assessment): Response|RedirectResponse
    {
        $studentId = Auth::id();

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->with([
                'assessment.classSubject.class',
                'assessment.classSubject.subject',
                'assessment.questions.choices',
                'answers',
            ])
            ->firstOrFail();

        if ($assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        if (! $assignment->started_at) {
            $assignment->update(['started_at' => now()]);
        }

        return Inertia::render('Student/Assessments/Take', [
            'assignment' => $assignment,
            'assessment' => $assessment,
        ]);
    }

    /**
     * Submit answers for an assessment.
     */
    public function submit(Request $request, Assessment $assessment)
    {
        $studentId = Auth::id();

        $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.answer_text' => ['nullable', 'string'],
            'answers.*.choice_ids' => ['nullable', 'array'],
        ]);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if ($assignment->submitted_at) {
            return back()->with('error', __('messages.assessment_already_submitted'));
        }

        foreach ($request->input('answers') as $answerData) {
            $assignment->answers()->updateOrCreate(
                [
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'] ?? null,
                    'choice_ids' => $answerData['choice_ids'] ?? null,
                ]
            );
        }

        $assignment->update(['submitted_at' => now()]);

        return redirect()
            ->route('student.assessments.show', $assessment)
            ->with('success', __('messages.assessment_submitted'));
    }

    /**
     * Display assessment results.
     */
    public function results(Assessment $assessment): Response|RedirectResponse
    {
        $studentId = Auth::id();

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->with([
                'assessment.classSubject.class',
                'assessment.classSubject.subject',
                'assessment.questions.choices',
                'answers.question',
            ])
            ->firstOrFail();

        if (! $assignment->submitted_at) {
            return redirect()->route('student.assessments.show', $assessment);
        }

        return Inertia::render('Student/Assessments/Results', [
            'assignment' => $assignment,
            'assessment' => $assessment,
        ]);
    }
}
