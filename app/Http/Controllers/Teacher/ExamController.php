<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Shared\UserAnswerService;
use App\Services\Teacher\ExamScoringService;
use App\Http\Requests\Teacher\StoreExamRequest;
use App\Services\Teacher\ExamAssignmentService;
use App\Services\Teacher\ExamGroupService;
use App\Http\Requests\Teacher\AssignExamRequest;
use App\Http\Requests\Teacher\UpdateExamRequest;
use App\Http\Requests\Teacher\UpdateScoreRequest;
use App\Http\Requests\Teacher\GetExamResultsRequest;
use App\Http\Requests\Teacher\SaveStudentReviewRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamService $examService,
        private ExamAssignmentService $examAssignmentService,
        private UserAnswerService $userAnswerService,
        private ExamScoringService $examScoringService,
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Display a listing of exams for the teacher.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\Response The response containing the list of exams.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $student */
        $student = $request->user();
        $perPage = $request->input('per_page', 10);

        $status = null;
        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status') === '1' ? true : false;
        }

        $search = $request->input('search');


        if (!$student) {
            abort(401);
        }

        $this->authorize('viewAny', Exam::class);

        $exams = $this->examService->getTeacherExams($student->id, $perPage, $status, $search);

        return Inertia::render('Teacher/ExamIndex', [
            'exams' => $exams
        ]);
    }

    /**
     * Display the form for creating a new exam.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): Response
    {
        $this->authorize('create', Exam::class);

        return Inertia::render('Teacher/ExamCreate');
    }

    /**
     * Store a newly created exam in storage.
     *
     * Handles the incoming request to create a new exam using the validated data
     * from the StoreExamRequest. Redirects the user after successful creation.
     *
     * @param  \App\Http\Requests\StoreExamRequest  $request  The validated request instance containing exam data.
     * @return \Illuminate\Http\RedirectResponse  Redirects to the appropriate route after storing the exam.
     */
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);

        try {

            $exam = $this->examService->createExam($request->validated());

            return $this->redirectWithSuccess(
                'teacher.exams.show',
                'Examen créé avec succès !',
                ['exam' => $exam->id]
            );
        } catch (\Exception $e) {

            return $this->redirectWithError(
                null,
                "Erreur lors de la création de l'examen : " . $e->getMessage()
            );
        }
    }

    /**
     * Display the specified exam details.
     *
     * @param  Exam  $exam  The exam instance to display.
     * @return Response The HTTP response containing exam details.
     */
    public function show(Exam $exam): Response
    {
        $this->authorize('view', $exam);

        $exam->load(['questions.choices']);
        $exam->loadCount([
            'questions'
        ]);

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);

        return Inertia::render('Teacher/ExamShow', [
            'exam' => $exam,
            'assignedGroups' => $assignedGroups
        ]);
    }

    /**
     * Show the form for editing the specified exam.
     *
     * @param  Exam  $exam  The exam instance to edit.
     * @return Response
     */
    public function edit(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $exam->load(['questions.choices']);

        return Inertia::render('Teacher/ExamEdit', [
            'exam' => $exam
        ]);
    }

    /**
     * Update the specified exam in storage.
     *
     * @param  \App\Http\Requests\UpdateExamRequest  $request  The validated request containing exam update data.
     * @param  \App\Models\Exam  $exam  The exam instance to update.
     * @return \Illuminate\Http\RedirectResponse  Redirect response after updating the exam.
     */
    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        try {

            $exam = $this->examService->updateExam($exam, $request->validated());

            return $this->redirectWithSuccess(
                'teacher.exams.show',
                'Examen mis à jour avec succès !',
                ['exam' => $exam->id]
            );
        } catch (\Exception $e) {

            return $this->redirectWithError(
                null,
                "Erreur lors de la mise à jour de l'examen : " . $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified exam from storage.
     *
     * @param  \App\Models\Exam  $exam  The exam instance to be deleted.
     * @return \Illuminate\Http\RedirectResponse Redirects to the previous page after deletion.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        try {
            $this->examService->deleteExam($exam);

            return $this->redirectWithSuccess(
                'teacher.exams.index',
                'Examen supprimé avec succès !'
            );
        } catch (\Exception $e) {

            return $this->redirectWithError(
                null,
                "Erreur lors de la suppression de l'examen : " . $e->getMessage()
            );
        }
    }

    /**
     * Duplicate the specified exam.
     *
     * Creates a copy of the given Exam instance and saves it as a new exam.
     *
     * @param  \App\Models\Exam  $exam  The exam to be duplicated.
     * @return \Illuminate\Http\RedirectResponse Redirects to the appropriate page after duplication.
     */
    public function duplicate(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $newExam = $this->examService->duplicateExam($exam);

            return $this->redirectWithSuccess(
                'teacher.exams.edit',
                'Examen dupliqué avec succès ! Vous pouvez maintenant le modifier.',
                ['exam' => $newExam->id]
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                null,
                "Erreur lors de la duplication de l'examen : " . $e->getMessage()
            );
        }
    }

    /**
     * Toggle the active status of the specified exam.
     *
     * This method switches the 'active' state of the given Exam instance.
     * After toggling, it redirects back to the previous page.
     *
     * @param Exam $exam The exam instance whose active status will be toggled.
     * @return RedirectResponse Redirects back to the previous page after toggling.
     */
    public function toggleActive(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        try {
            $exam->update(['is_active' => !$exam->is_active]);

            $status = $exam->is_active ? 'activé' : 'désactivé';

            return $this->flashSuccess("Examen {$status} avec succès !");
        } catch (\Exception $e) {

            return $this->flashError("Erreur lors du changement de statut de l'examen : " . $e->getMessage());
        }
    }

    /**
     * Display statistics for the specified exam.
     */
    public function stats(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        // TODO: Implémenter les statistiques et créer la vue
        return $this->flashInfo('Les statistiques seront disponibles prochainement.');
    }

    /**
     * Display the form to assign an exam to a teacher.
     *
     * @param Exam $exam The exam instance to be assigned.
     * @return Response The response containing the assignment form view.
     */
    public function showAssignForm(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);


        $availableGroups = $this->examGroupService->getAvailableGroupsForExam($exam);

        $legacyAssignments = $this->examAssignmentService->getAssignmentFormData($exam);

        return Inertia::render('Teacher/ExamAssign', [
            'exam' => $exam,
            'assignedGroups' => $assignedGroups,
            'availableGroups' => $availableGroups,
            'students' => $legacyAssignments['students'],
        ]);
    }

    /**
     * Assigns the specified exam to selected students.
     *
     * Handles the assignment of an exam to students based on the validated request data.
     *
     * @param AssignExamRequest $request The validated request containing assignment details.
     * @param Exam $exam The exam instance to be assigned.
     * @return RedirectResponse Redirects back with a status message upon completion.
     */
    public function assignToStudents(AssignExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $result = $this->examAssignmentService->assignExamToStudents(
            $exam,
            $request->validated()['student_ids']
        );

        $message = "Assignation terminée : {$result['assigned_count']} nouveaux étudiants assignés";
        if ($result['already_assigned_count'] > 0) {
            $message .= " ({$result['already_assigned_count']} déjà assignés)";
        }

        return $this->redirectWithSuccess('teacher.exams.show', $message, ['exam' => $exam->id]);
    }

    /**
     * Assigns the specified exam to one or multiple groups.
     *
     * @param Request $request The request containing the group IDs.
     * @param Exam $exam The exam instance to be assigned.
     * @return RedirectResponse Redirects back with a status message upon completion.
     */
    public function assignToGroups(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'required|exists:groups,id',
        ]);

        $result = $this->examGroupService->assignExamToGroups(
            $exam,
            $validated['group_ids']
        );

        $message = "Assignation terminée : {$result['assigned_count']} groupe(s) assigné(s)";
        if ($result['already_assigned_count'] > 0) {
            $message .= " ({$result['already_assigned_count']} déjà assigné(s))";
        }

        return $this->redirectWithSuccess('teacher.exams.show', $message, ['exam' => $exam->id]);
    }

    /**
     * Remove the exam assignment from a group.
     *
     * @param Exam $exam The exam instance.
     * @param int $groupId The group ID.
     * @return RedirectResponse Redirects back with a status message.
     */
    public function removeFromGroup(Exam $exam, int $groupId): RedirectResponse
    {
        $this->authorize('update', $exam);

        $group = \App\Models\Group::findOrFail($groupId);

        $removed = $this->examGroupService->removeExamFromGroup($exam, $group);

        if ($removed) {
            return $this->flashSuccess("L'examen a été retiré du groupe avec succès.");
        }

        return $this->flashError("Impossible de retirer l'examen de ce groupe.");
    }

    /**
     * Removes the assignment of the specified user from the given exam.
     *
     * @param Exam $exam The exam instance from which the user assignment will be removed.
     * @param \App\Models\User $user The user whose assignment is to be removed from the exam.
     * @return RedirectResponse Redirects back to the previous page or a specified route after removal.
     */
    public function removeAssignment(Exam $exam, \App\Models\User $user): RedirectResponse
    {
        $this->authorize('update', $exam);

        $assignment = $exam->assignments()->where('student_id', $user->id)->first();

        if (!$assignment) {

            return $this->redirectWithError('teacher.exams.show', "Cet étudiant n'est pas assigné à cet examen.", ['exam' => $exam->id]);
        }

        $assignment->delete();

        return $this->redirectWithSuccess('teacher.exams.show', "Assignation de {$user->name} supprimée avec succès.", ['exam' => $exam->id]);
    }

    /**
     * Display the assignments related to the specified exam.
     *
     * @param Exam $exam The exam instance for which assignments are to be shown.
     * @param GetExamResultsRequest $request The request containing parameters for fetching exam results.
     * @return Response The HTTP response containing the assignments data.
     */
    public function showAssignments(Exam $exam, GetExamResultsRequest $request): Response
    {
        $this->authorize('view', $exam);

        $params = $request->validatedWithDefaults();
        $data = $this->examAssignmentService->getPaginatedAssignments($exam, $params);

        return Inertia::render('Teacher/ExamAssignments', $data);
    }

    /**
     * Display the results of a specific exam for a given student.
     *
     * @param Exam $exam The exam instance for which results are to be shown.
     * @param User $student The student whose exam results are to be displayed.
     * @return Response The HTTP response containing the student's exam results.
     */
    public function showStudentResults(Exam $exam, User $student): Response
    {
        $this->authorize('view', $exam);

        $assignment = $exam->assignments()->where('student_id', $student->id)->firstOrFail();

        $data = $this->userAnswerService->getStudentResultsData($assignment);

        return Inertia::render('Teacher/ExamStudentResults', $data);
    }

    /**
     * Display the review of a student's exam.
     *
     * @param Exam $exam The exam instance to be reviewed.
     * @param \App\Models\User $student The student whose exam review is to be shown.
     * @return Response The HTTP response containing the student's exam review.
     */
    public function showStudentReview(Exam $exam, \App\Models\User $student): Response
    {
        $this->authorize('view', $exam);

        $assignment = $exam->assignments()->where('student_id', $student->id)->firstOrFail();

        $data = $this->userAnswerService->getStudentReviewData($assignment);

        return Inertia::render('Teacher/ExamStudentReview', $data);
    }


    /**
     * Saves a review for a student on a specific exam.
     *
     * @param SaveStudentReviewRequest $request The validated request containing review data.
     * @param Exam $exam The exam instance being reviewed.
     * @param \App\Models\User $student The student for whom the review is being saved.
     * @return RedirectResponse Redirects back after saving the review.
     */
    public function saveStudentReview(SaveStudentReviewRequest $request, Exam $exam, \App\Models\User $student): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $result = $this->examScoringService->saveManualCorrection($exam, $student, $request->validated());

            return $this->redirectWithSuccess(
                'teacher.exams.review',
                "Correction sauvegardée avec succès ! {$result['updated_answers']} réponses mises à jour. Note total: {$result['total_score']} points.",
                ['exam' => $exam->id, 'student' => $student->id]
            );
        } catch (\Exception $e) {

            Log::error("Erreur lors de la sauvegarde de la correction : " . $e->getMessage());

            return $this->redirectWithError(
                'teacher.exams.review',
                'Erreur lors de la sauvegarde de la correction',
                ['exam' => $exam->id, 'student' => $student->id]
            );
        }
    }

    /**
     * Mettre à jour le score d'une question spécifique
     */
    /**
     * Updates the score for a given exam based on the provided request data.
     *
     * @param  UpdateScoreRequest  $request  The validated request containing score update information.
     * @param  Exam  $exam  The exam instance to update the score for.
     * @return JsonResponse  The JSON response indicating the result of the update operation.
     */
    public function updateScore(UpdateScoreRequest $request, Exam $exam): JsonResponse
    {
        try {
            $validated = $request->validated();

            $studentId = $validated['student_id'] ?? request()->route('student');
            $assignment = $exam->assignments()->where('student_id', $studentId)->firstOrFail();

            $this->examScoringService->saveTeacherCorrections($assignment, [
                'question_id' => $validated['question_id'],
                'score' => $validated['score'],
                'teacher_notes' => $validated['teacher_notes'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Note mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la note'
            ], 422);
        }
    }
}
