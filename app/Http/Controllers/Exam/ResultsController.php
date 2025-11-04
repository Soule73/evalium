<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Group;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Core\Answer\AnswerFormatterService;
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
        private AnswerFormatterService $answerFormatter
    ) {}

    /**
     * Display the results of a specific exam for a given student.
     *
     * @param Exam $exam The exam instance for which results are to be shown.
     * @param Group $group The group to which the student belongs.
     * @param User $student The student whose exam results are to be displayed.
     * @return Response The HTTP response containing the student's exam results.
     */
    public function showStudentSubmission(Exam $exam, Group $group, User $student): Response
    {
        $this->authorize('view', $exam);

        $data = $this->answerFormatter->getStudentResultsDataInGroup($exam, $group, $student);

        return Inertia::render('Exam/StudentResults', $data);
    }

    /**
     * Display statistics for the specified exam.
     * 
     * TODO: Implement statistics view with score distribution,
     * average, median, and question-level analytics.
     *
     * @param Exam $exam The exam instance for which statistics are to be shown.
     * @return RedirectResponse Redirects back with a message.
     */
    public function stats(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        return $this->flashInfo('Statistics will be available soon.');
    }
}
