<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service pour gérer les assignations des examens aux étudiants
 * 
 * @package App\Services\Exam
 */
class ExamAssignmentService
{
    public function __construct(
        private ExamGroupService $examGroupService
    ) {}

    /**
     * Récupérer les assignations d'un examen avec pagination et filtres
     */
    public function getExamAssignments(
        Exam $exam,
        int $perPage = 10,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $query = $exam->assignments()
            ->with('student')
            ->orderBy('assigned_at', 'desc');

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Calculer les statistiques des assignations d'un examen
     */
    public function getExamAssignmentStats(Exam $exam): array
    {
        // Récupérer tous les groupes assignés et leurs étudiants actifs
        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);
        $totalStudentsInGroups = $assignedGroups->sum(function ($group) {
            return $group->activeStudents->count();
        });

        // Récupérer toutes les assignations existantes
        $allAssignments = $exam->assignments()->get();
        $assignedStudentsCount = $allAssignments->count();

        // Calculer combien d'étudiants n'ont pas encore d'assignation créée
        $notAssignedYet = $totalStudentsInGroups - $assignedStudentsCount;

        // Compter les statuts basés sur les timestamps
        $inProgressCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStartedCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $completedCount = $allAssignments->whereIn('status', ['submitted', 'graded'])->count();

        return [
            'total_assigned' => $totalStudentsInGroups,
            'total_submitted' => $completedCount,
            'completed' => $completedCount,
            'in_progress' => $inProgressCount,
            'not_started' => $notStartedCount + $notAssignedYet,
            'completion_rate' => $totalStudentsInGroups > 0 ?
                ($completedCount / $totalStudentsInGroups) * 100 : 0,
            'average_score' => $allAssignments->whereNotNull('score')->avg('score')
        ];
    }

    /**
     * Assigner un examen à plusieurs étudiants
     */
    public function assignExamToStudents(Exam $exam, array $studentIds): array
    {
        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        foreach ($studentIds as $studentId) {
            $result = $this->assignExamToStudent($exam, $studentId);

            if ($result['was_created']) {
                $assignedCount++;
            } else {
                $alreadyAssignedCount++;
            }
        }

        return [
            'success' => true,
            'assigned_count' => $assignedCount,
            'already_assigned_count' => $alreadyAssignedCount,
            'total_students' => count($studentIds)
        ];
    }

    /**
     * Assigner un examen à tous les étudiants d'un groupe
     */
    public function assignExamToGroup(Exam $exam, int $groupId): array
    {
        $group = Group::with('activeStudents')->findOrFail($groupId);

        $studentIds = $group->activeStudents->pluck('id')->toArray();

        return $this->assignExamToStudents($exam, $studentIds);
    }

    /**
     * Assigner un examen à un étudiant spécifique
     */
    public function assignExamToStudent(Exam $exam, int $studentId): array
    {
        $student = User::find($studentId);
        if (!$student || !$student->hasRole('student')) {
            throw new \InvalidArgumentException("L'utilisateur avec l'ID {$studentId} n'est pas un étudiant valide.");
        }

        $assignment = ExamAssignment::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
        ], [
            'assigned_at' => now(),
        ]);

        return [
            'assignment' => $assignment,
            'was_created' => $assignment->wasRecentlyCreated
        ];
    }

    /**
     * Supprimer l'assignation d'un étudiant
     */
    public function removeStudentAssignment(Exam $exam, User $student): bool
    {
        $assignment = $exam->assignments()->where('student_id', $student->id)->first();

        if (!$assignment) {
            throw new \InvalidArgumentException("Cet étudiant n'est pas assigné à cet examen.");
        }

        return $assignment->delete();
    }

    /**
     * Récupérer l'assignation d'un étudiant pour un examen avec toutes les relations nécessaires
     */
    public function getStudentAssignmentWithAnswers(Exam $exam, User $student): ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $student->id)
            ->with(['answers.question.choices', 'answers.choice'])
            ->firstOrFail();
    }

    /**
     * Récupérer l'assignation soumise d'un étudiant pour un examen
     */
    public function getSubmittedStudentAssignment(Exam $exam, User $student): ExamAssignment
    {
        return $exam->assignments()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->with(['answers.question.choices', 'answers.choice'])
            ->firstOrFail();
    }

    /**
     * Récupérer les données pour le formulaire d'assignation
     */
    public function getAssignmentFormData(Exam $exam): array
    {
        $exam->load(['questions', 'assignments.student']);

        $students = User::role('student')
            ->with(['activeGroup'])
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $groups = Group::active()
            ->with(['activeStudents', 'level'])
            ->orderBy('academic_year', 'desc')
            ->get();

        $assignedStudentIds = $exam->assignments()
            ->pluck('student_id')
            ->toArray();

        return [
            'exam' => $exam,
            'students' => $students,
            'groups' => $groups,
            'alreadyAssigned' => $assignedStudentIds,
            'assignedStudentIds' => $assignedStudentIds
        ];
    }

    /**
     * Récupérer les assignations paginées avec filtres et statistiques
     */
    public function getPaginatedAssignments(Exam $exam, array $params): array
    {
        $query = $exam->assignments()
            ->with('student')
            ->orderBy($params['sort_by'] === 'user_name' ? 'assigned_at' : $params['sort_by'], $params['sort_direction']);

        if ($params['search']) {
            $query->whereHas('student', function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('email', 'like', '%' . $params['search'] . '%');
            });
        }

        if ($params['filter_status']) {
            $query->where('status', $params['filter_status']);
        }

        $assignments = $query->paginate($params['per_page'])->withQueryString();

        // Récupérer tous les groupes assignés et leurs étudiants actifs
        $assignedGroups = $this->examGroupService->getGroupsForExam($exam);
        $totalStudentsInGroups = $assignedGroups->sum(function ($group) {
            return $group->activeStudents->count();
        });

        // Récupérer toutes les assignations existantes
        $allAssignments = $exam->assignments()->get();
        $assignedStudentsCount = $allAssignments->count();

        // Calculer combien d'étudiants n'ont pas encore d'assignation créée
        $notAssignedYet = $totalStudentsInGroups - $assignedStudentsCount;

        // Compter les statuts basés sur les timestamps
        $inProgressCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at !== null && $assignment->submitted_at === null;
        })->count();

        $notStartedCount = $allAssignments->filter(function ($assignment) {
            return $assignment->started_at === null;
        })->count();

        $stats = [
            'total_assigned' => $totalStudentsInGroups,
            'completed' => $allAssignments->whereIn('status', ['submitted', 'graded'])->count(),
            'in_progress' => $inProgressCount,
            'not_started' => $notStartedCount + $notAssignedYet,
            'average_score' => $allAssignments->whereNotNull('score')->avg('score')
        ];

        return [
            'exam' => $exam,
            'assignments' => $assignments,
            'stats' => $stats,
            'assignedGroups' => $assignedGroups
        ];
    }
}
