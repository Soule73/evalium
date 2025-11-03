<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\ExamService;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamGroupService;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * ExamController - Contrôleur UNIFIÉ pour la gestion des examens (NON-STUDENTS)
 * 
 * ARCHITECTURE :
 * - Student/ExamController : Actions STRICT student-only (take, submit, abandon)
 * - ExamController (ce fichier) : Toutes les autres actions basées sur PERMISSIONS
 * 
 * Les permissions sont vérifiées par le MIDDLEWARE dans routes/web.php
 * Pas besoin de re-vérifier dans le contrôleur (pas de duplication)
 * 
 * Les POLICIES vérifient l'accès au niveau du modèle (ownership, etc.)
 */
class ExamController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamService $examService,
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Liste des examens - Adapté selon les permissions de l'utilisateur.
     * Middleware vérifie déjà 'view exams' permission.
     * Policy vérifie l'accès au niveau modèle.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        $status = null;
        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status') === '1' ? true : false;
        }

        $search = $request->input('search');

        // Policy vérifie viewAny
        $this->authorize('viewAny', Exam::class);

        // Service adapte automatiquement selon les permissions de l'utilisateur
        $exams = $this->examService->getExams($user->id, $perPage, $status, $search);

        return Inertia::render('Exam/Index', [
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

        return Inertia::render('Exam/Create');
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
                'exams.show',
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
        $exam->loadCount(['questions']);

        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);

        return Inertia::render('Exam/Show', [
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

        return Inertia::render('Exam/Edit', [
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
        // dd($request->all());
        $this->authorize('update', $exam);

        try {
            $exam = $this->examService->updateExam($exam, $request->validated());

            return $this->redirectWithSuccess(
                'exams.show',
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
                'exams.index',
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
                'exams.edit',
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
}
