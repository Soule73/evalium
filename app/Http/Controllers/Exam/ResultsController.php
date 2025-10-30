<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Shared\UserAnswerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for displaying exam results and statistics.
 * 
 * This controller handles:
 * - Displaying individual student results for an exam
 * - Displaying exam statistics (TODO)
 */
class ResultsController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private UserAnswerService $userAnswerService
    ) {}

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

        return Inertia::render('Exam/StudentResults', $data);
    }

    /**
     * Display statistics for the specified exam.
     *
     * @param Exam $exam The exam instance for which statistics are to be shown.
     * @return RedirectResponse Redirects back with a message.
     */
    public function stats(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        // TODO: Implémenter les statistiques et créer la vue
        return $this->flashInfo('Les statistiques seront disponibles prochainement.');
    }
}
