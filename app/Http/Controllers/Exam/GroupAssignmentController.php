<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use App\Models\Group;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use Illuminate\Http\RedirectResponse;
use App\Services\Exam\ExamGroupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller responsible for managing group assignments to exams.
 * 
 * This controller handles:
 * - Assigning exams to groups of students
 * - Removing group assignments
 */
class GroupAssignmentController extends Controller
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

        return $this->redirectWithSuccess('exams.show', $message, ['exam' => $exam->id]);
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

    /**
     * Show group details with students and their exam status.
     *
     * @param Exam $exam The exam instance.
     * @param Group $group The group instance.
     * @param Request $request The request instance.
     * @return Response The response containing group details.
     */
    public function showGroupDetails(Exam $exam, Group $group, Request $request): Response
    {
        $this->authorize('view', $exam);

        // Charger le groupe avec ses relations
        $group->load(['level', 'activeStudents']);

        // Paramètres de pagination et filtres
        $perPage = $request->input('per_page', 10);
        $status = $request->input('filter_status');
        $search = $request->input('search');
        $page = $request->input('page', 1);

        // Récupérer tous les étudiants actifs du groupe
        $studentsQuery = $group->activeStudents();

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Récupérer les étudiants avec pagination
        $students = $studentsQuery->paginate($perPage, ['*'], 'page', $page);

        // Récupérer les assignations pour ces étudiants
        $studentIds = $students->pluck('id')->toArray();
        $assignments = $exam->assignments()
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        // Créer un tableau de données combinées (étudiants + assignations)
        $combinedData = $students->map(function ($student) use ($assignments, $exam) {
            $assignment = $assignments->get($student->id);

            // Si pas d'assignation, créer un objet par défaut
            if (!$assignment) {
                return (object)[
                    'id' => null,
                    'student_id' => $student->id,
                    'student' => $student,
                    'exam_id' => $exam->id,
                    'status' => 'not_assigned',
                    'assigned_at' => null,
                    'started_at' => null,
                    'submitted_at' => null,
                    'score' => null,
                ];
            }

            // Ajouter l'étudiant à l'assignation
            $assignment->student = $student;
            return $assignment;
        });

        // Filtrer par statut si nécessaire
        if ($status) {
            $combinedData = $combinedData->filter(function ($item) use ($status) {
                return $item->status === $status;
            });
        }

        // Reconstruire la pagination manuellement
        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedData->values(),
            $students->total(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Calculer les statistiques pour tout le groupe
        $allStudents = $group->activeStudents;
        $allAssignments = $exam->assignments()
            ->whereIn('student_id', $allStudents->pluck('id')->toArray())
            ->get();

        $totalStudents = $allStudents->count();
        $assignedStudents = $allAssignments->count();
        $notAssigned = $totalStudents - $assignedStudents;

        $stats = [
            'total_students' => $totalStudents,
            'completed' => $allAssignments->whereIn('status', ['submitted', 'graded'])->count(),
            'in_progress' => $allAssignments->where('status', 'started')->count(),
            'not_started' => $allAssignments->where('status', 'assigned')->count() + $notAssigned,
            'average_score' => $allAssignments->whereNotNull('score')->avg('score')
        ];

        return Inertia::render('Exam/GroupDetails', [
            'exam' => $exam->load('questions'),
            'group' => $group,
            'assignments' => $paginatedData->withQueryString(),
            'stats' => $stats
        ]);
    }
}
