<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Exam;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Teacher\ExamGroupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for managing group assignments to exams.
 * 
 * This controller handles:
 * - Assigning exams to groups of students
 * - Removing group assignments
 */
class ExamGroupAssignmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private ExamGroupService $examGroupService
    ) {}

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

        $group = Group::findOrFail($groupId);

        $removed = $this->examGroupService->removeExamFromGroup($exam, $group);

        if ($removed) {
            return $this->flashSuccess("L'examen a été retiré du groupe avec succès.");
        }

        return $this->flashError("Impossible de retirer l'examen de ce groupe.");
    }
}
