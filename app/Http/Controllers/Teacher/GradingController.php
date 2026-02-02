<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use App\Services\Core\GradeCalculationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradingController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService
    ) {}

    /**
     * Display grading interface for an assessment.
     */
    public function index(Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class.students',
            'assignments.student',
            'assignments.answers.question',
            'questions',
        ]);

        return Inertia::render('Teacher/Grading/Index', [
            'assessment' => $assessment,
        ]);
    }

    /**
     * Display grading interface for a specific student.
     */
    public function show(Assessment $assessment, User $student): Response
    {
        $this->authorize('view', $assessment);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->with([
                'student',
                'assessment.questions.choices',
                'answers.question',
            ])
            ->firstOrFail();

        return Inertia::render('Teacher/Grading/Show', [
            'assignment' => $assignment,
            'assessment' => $assessment,
        ]);
    }

    /**
     * Save manual grade for a specific answer or assignment.
     */
    public function save(Request $request, Assessment $assessment, User $student): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $request->validate([
            'answers' => ['sometimes', 'array'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.score' => ['required', 'numeric', 'min:0'],
            'answers.*.feedback' => ['nullable', 'string'],
            'total_score' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($request->has('answers')) {
            foreach ($request->input('answers') as $answerData) {
                $answer = $assignment->answers()
                    ->where('question_id', $answerData['question_id'])
                    ->first();

                if ($answer) {
                    $answer->update([
                        'score' => $answerData['score'],
                        'feedback' => $answerData['feedback'] ?? null,
                    ]);
                }
            }

            $totalScore = $assignment->answers()->sum('score');
            $assignment->update(['score' => $totalScore]);
        } elseif ($request->has('total_score')) {
            $assignment->update(['score' => $request->input('total_score')]);
        }

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
