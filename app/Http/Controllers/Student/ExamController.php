<?php

namespace App\Http\Controllers\Student;

use App\Models\Exam;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\ExamService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use App\Services\Shared\UserAnswerService;
use App\Services\Student\ExamSessionService;
use App\Http\Requests\Student\SubmitExamRequest;
use App\Http\Requests\Student\SaveAnswersRequest;
use App\Http\Requests\Student\SecurityViolationRequest;

class ExamController extends Controller
{
    use HasFlashMessages;

    public function __construct(
        private readonly ExamService $examService,
        private readonly ExamSessionService $examSessionService,
        private readonly UserAnswerService $userAnswerService
    ) {}

    /**
     * Display a listing of exams for the student.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request instance.
     * @return \Inertia\Response  The Inertia response containing the exams data.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var \App\Models\User $student */
        $student = $request->user();
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status') ? $request->input('status') : null;
        $search = $request->input('search');

        $perPage = $request->input('per_page', 10);


        if (!$student) {
            abort(401);
        }

        $assignments = $this->examService->getAssignedExamsForStudent($student, $perPage, $status, $search);

        return Inertia::render('Student/ExamIndex', [
            'pagination' => $assignments,
        ]);
    }

    /**
     * Display the specified exam details for the student.
     *
     * @param  \App\Models\Exam  $exam  The exam instance to display.
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance.
     * @return \Inertia\Response  The Inertia response containing exam details.
     */
    public function show(Exam $exam, Request $request): InertiaResponse
    {
        /** @var \App\Models\User $student */
        $student = $request->user();

        if (!$student) {
            abort(401);
        }

        $assignment = $this->examService->getAssignedExamForStudent($exam, $student->id);

        $canTake = $this->examService->canTakeExam($exam, $assignment);

        $creator = $exam->teacher;
        if ($canTake) {
            $questionsCount = $exam->questions()->count();


            return Inertia::render('Student/ExamShow', [
                'exam' => $exam,
                'assignment' => $assignment,
                'canTake' => $canTake,
                'questionsCount' => $questionsCount,
                'creator' => $creator,
            ]);
        } else {
            // Vérifier que l'examen est noté avant d'afficher les résultats
            if ($assignment->status !== 'graded') {
                abort(403, 'Les résultats ne sont pas encore disponibles.');
            }

            $exam->load(['questions.choices']);

            $userAnswers = $this->userAnswerService->formatUserAnswersForFrontend($assignment);

            return Inertia::render('Student/ExamResults', [
                'exam' => $exam,
                'assignment' => $assignment,
                'userAnswers' => $userAnswers,
                'creator' => $creator,
            ]);
        }
    }


    /**
     * Display the exam for the student to take or handle the exam submission.
     *
     * @param  \App\Models\Exam  $exam  The exam instance to be taken.
     * @param  \Illuminate\Http\Request  $request  The current HTTP request.
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function take(Exam $exam, Request $request): InertiaResponse|RedirectResponse
    {
        /** @var \App\Models\User $student */
        $student = $request->user();
        if (!$student) {
            abort(401);
        }

        $assignment = $this->examSessionService->findOrCreateAssignment($exam, $student);

        if (!$this->examService->examIsActive($exam)) {
            return $this->redirectWithError('student.exams.show', "Cet examen n'est pas disponible.", ['exam' => $exam->id]);
        }

        if (!$this->examSessionService->validateExamTiming($exam)) {
            return $this->redirectWithError('student.exams.show', "Cet examen n'est pas accessible actuellement.", ['exam' => $exam->id]);
        }

        if (!$this->examService->canTakeExam($exam, $assignment)) {
            return $this->redirectWithInfo('student.exams.show', "Vous avez déjà complété cet examen.", ['exam' => $exam->id]);
        }

        $this->examSessionService->startExam($assignment);

        $exam->load(['questions.choices']);

        $userAnswers = $this->userAnswerService->formatUserAnswersForFrontend($assignment);

        return Inertia::render('Student/TakeExam', [
            'exam' => $exam,
            'assignment' => $assignment,
            'questions' => $exam->questions,
            'userAnswers' => $userAnswers,
        ]);
    }

    /**
     * Handles the saving of student answers for a given exam.
     *
     * @param SaveAnswersRequest $request The validated request containing the student's answers.
     * @param Exam $exam The exam instance for which the answers are being saved.
     * @return JsonResponse Returns a JSON response indicating the result of the save operation.
     */
    public function saveAnswers(SaveAnswersRequest $request, Exam $exam): JsonResponse
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();
        if (!$student) {
            abort(401);
        }

        $assignment = $this->examService->getStartedExamForStudent($exam, $student->id);

        try {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $request->validated()['answers']);

            return response()->json([
                'success' => true,
                'message' => 'Réponses sauvegardées'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handles a security violation during an exam attempt.
     *
     * This method processes the incoming security violation request for a given exam.
     * It may log the violation, notify administrators, or take appropriate action
     * based on the application's security policies.
     *
     * @param SecurityViolationRequest $request The request containing security violation details.
     * @param Exam $exam The exam instance related to the violation.
     * @return JsonResponse The JSON response to be sent back to the client.
     */
    public function handleSecurityViolation(SecurityViolationRequest $request, Exam $exam): JsonResponse
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();
        if (!$student) {
            abort(401);
        }

        $assignment =  $this->examService->getStartedExamForStudent($exam, $student->id);

        $validated = $request->validated();

        if (isset($validated['answers'])) {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $validated['answers']);
        }

        $autoScore = $this->examSessionService->calculateAutoScore($assignment);
        $assignment->update(['auto_score' => $autoScore, 'submitted_at' => Carbon::now(), 'status' => 'pending_review']);

        $this->examSessionService->handleViolation(
            $assignment,
            $validated['violation_type'],
            $validated['violation_details'] ?? '',
            $validated['answers'] ?? []
        );

        // Recharger l'assignment pour avoir les données à jour
        $assignment->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Violation de sécurité traitée',
            'exam_terminated' => true,
            'violation_type' => $validated['violation_type'],
            'violation_details' => $validated['violation_details'] ?? '',
            'assignment' => $assignment
        ]);
    }


    /**
     * Allows a student to abandon an ongoing exam.
     *
     * @param Exam $exam The exam instance to be abandoned.
     * @return Response The HTTP response indicating the result of the operation.
     */
    public function abandon(Exam $exam): Response
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();
        if (!$student) {
            abort(401);
        }

        $assignment = $this->examService->getAssignedExamForStudent($exam, $student->id);

        if (!$assignment) {
            abort(404, 'Examen non trouvé ou déjà soumis');
        }

        $this->examSessionService->submitExam($assignment, false, true);

        return response('', 200);
    }


    /**
     * Handles the submission of an exam by a student.
     *
     * Validates the submitted exam data using the SubmitExamRequest,
     * processes the exam submission for the given Exam instance,
     * and redirects the user to the appropriate page after submission.
     *
     * @param SubmitExamRequest $request The validated request containing exam answers.
     * @param Exam $exam The exam being submitted.
     * @return RedirectResponse Redirects to the result or confirmation page.
     */
    public function submit(SubmitExamRequest $request, Exam $exam): RedirectResponse
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();
        if (!$student) {
            abort(401);
        }

        $assignment = $this->examService->getAssignedExamForStudent($exam, $student->id);

        // Vérifier que l'examen a été commencé
        if ($assignment->status !== 'started') {
            return back()->withErrors(['exam' => 'Vous devez commencer l\'examen avant de le soumettre.']);
        }

        $validated = $request->validated();

        if (isset($validated['answers'])) {
            $this->examSessionService->saveMultipleAnswers($assignment, $exam, $validated['answers']);
        }

        $autoScore = $this->examSessionService->calculateAutoScore($assignment);
        $hasTextQuestions = $exam->questions()->where('type', 'text')->exists();
        $isSecurityViolation = $validated['security_violation'] ?? false;

        $this->examSessionService->submitExam($assignment, $autoScore, $hasTextQuestions, $isSecurityViolation);

        $message = 'Examen soumis avec succès !';

        return $this->redirectWithSuccess('student.exams.show', $message, ['exam' => $exam->id]);
    }
}
