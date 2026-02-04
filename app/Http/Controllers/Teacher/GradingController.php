<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\SaveManualGradeRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use App\Services\Core\GradeCalculationService;
use App\Services\Core\Scoring\ScoringService;
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
        private readonly ScoringService $scoringService
    ) {}

    /**
     * Display grading interface for an assessment.
     */
    public function index(Request $request, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $assessment->load([
            'classSubject.class',
            'questions',
        ]);

        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignments = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->with(['student', 'answers.question'])
            ->paginate(
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

        $assessment->load(['classSubject.class', 'questions.choices']);
        $this->validateAcademicYearAccess($assessment->classSubject->class, $selectedYearId);

        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->with(['answers.question', 'answers.choice'])
            ->firstOrFail();

        $userAnswers = [];
        foreach ($assignment->answers->groupBy('question_id') as $questionId => $answers) {
            if ($answers->count() === 1) {
                $userAnswers[$questionId] = $answers->first();
            } else {
                $firstAnswer = $answers->first();
                $firstAnswer->choices = $answers->filter(function ($answer) {
                    return $answer->choice_id !== null;
                })->map(function ($answer) {
                    return ['choice' => $answer->choice];
                })->values()->all();

                $userAnswers[$questionId] = $firstAnswer;
            }
        }

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
        $assignment = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        foreach ($request->input('scores', []) as $scoreData) {
            $answers = $assignment->answers()
                ->where('question_id', $scoreData['question_id'])
                ->get();

            if ($answers->isNotEmpty()) {
                $answers->first()->update([
                    'score' => $scoreData['score'],
                    'feedback' => $scoreData['feedback'] ?? null,
                ]);

                $answers->skip(1)->each(function ($answer) use ($scoreData) {
                    $answer->update([
                        'score' => 0,
                        'feedback' => $scoreData['feedback'] ?? null,
                    ]);
                });
            }
        }

        $totalScore = $this->scoringService->calculateAssignmentScore($assignment);

        $assignment->update([
            'score' => $totalScore,
            'teacher_notes' => $request->input('teacher_notes'),
        ]);

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
