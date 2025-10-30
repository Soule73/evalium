<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class ExamService
{

    /**
     * List of statuses that indicate an exam is currently active.
     * Exams with these statuses are considered as ongoing or in progress.
     * 
     * Possible values:
     * - 'assigned': The exam has been assigned to a user.
     * - 'started': The user has started the exam.
     *
     * @var string[]
     */
    private const EXAM_ACTIVE_STATUSES = ['assigned', 'started'];

    /**
     * List of statuses that indicate an exam has been completed.
     * 
     * Possible values:
     * - 'submitted': The exam has been submitted by the user.
     * - 'pending_review': The exam is awaiting review.
     * - 'graded': The exam has been graded.
     * @var string[]
     */
    private const EXAM_COMPLETED_STATUSES = ['submitted', 'pending_review', 'graded'];

    /**
     * Filters the given collection of assignments to include only active assignments.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, ExamAssignment> $assignments The collection of assignments to filter.
     * @return \Illuminate\Database\Eloquent\Collection<int, ExamAssignment> The filtered collection containing only active assignments.
     */
    public function filterActiveAssignments(\Illuminate\Database\Eloquent\Collection $assignments): \Illuminate\Database\Eloquent\Collection
    {
        return $assignments->whereIn('status', self::EXAM_ACTIVE_STATUSES);
    }

    /**
     * Filters the given list of assignments and returns only those that are completed.
     *
     */
    /**
     * Filters the given collection of assignments to return only those that are completed.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, ExamAssignment> $assignments The collection of assignments to filter.
     * @return \Illuminate\Database\Eloquent\Collection<int, ExamAssignment> The filtered collection containing only completed assignments.
     */
    public function filterCompletedAssignments(Collection $assignments): Collection
    {
        return $assignments->whereIn('status', self::EXAM_COMPLETED_STATUSES);
    }


    /**
     * Determines if the given exam can be taken by the user based on the provided assignment.
     *
     * @param Exam $exam The exam to be taken.
     * @param ExamAssignment $assignment The assignment details for the exam.
     * @return bool True if the exam can be taken, false otherwise.
     */
    public function canTakeExam(Exam $exam, ExamAssignment $assignment): bool
    {
        return $assignment &&
            in_array($assignment->status, self::EXAM_ACTIVE_STATUSES) &&
            $this->examIsActive($exam);
    }

    /**
     * Determines if the given exam is currently active.
     *
     * @param Exam $exam The exam instance to check.
     * @return bool True if the exam is active, false otherwise.
     */
    public function examIsActive(Exam $exam): bool
    {
        return $exam->is_active;
    }

    /**
     * Créer un nouvel examen avec ses questions
     */
    public function createExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {

            $exam = Exam::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'teacher_id' => Auth::id(),
            ]);

            $this->createQuestions($exam, $data['questions']);

            return $exam->load(['questions.choices']);
        });
    }

    /**
     * Mettre à jour un examen existant
     */
    public function updateExam(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $exam->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            if (isset($data['deletedQuestionIds']) && is_array($data['deletedQuestionIds']) && !empty($data['deletedQuestionIds'])) {

                $validQuestionIds = Question::where('exam_id', $exam->id)
                    ->whereIn('id', $data['deletedQuestionIds'])
                    ->pluck('id')
                    ->toArray();

                if (!empty($validQuestionIds)) {
                    Answer::whereIn('question_id', $validQuestionIds)->delete();
                    Choice::whereIn('question_id', $validQuestionIds)->delete();
                    Question::whereIn('id', $validQuestionIds)->delete();
                }
            }

            if (isset($data['deletedChoiceIds']) && is_array($data['deletedChoiceIds']) && !empty($data['deletedChoiceIds'])) {
                $validChoiceIds = \App\Models\Choice::whereHas('question', function ($query) use ($exam) {
                    $query->where('exam_id', $exam->id);
                })
                    ->whereIn('id', $data['deletedChoiceIds'])
                    ->pluck('id')
                    ->toArray();

                if (!empty($validChoiceIds)) {
                    Answer::whereIn('choice_id', $validChoiceIds)->delete();
                    Choice::whereIn('id', $validChoiceIds)->delete();
                }
            }

            if (isset($data['questions']) && is_array($data['questions'])) {
                $this->updateQuestions($exam, $data['questions']);
            }

            return $exam->load(['questions.choices']);
        });
    }

    /**
     * Supprimer explicitement une question 
     */
    public function deleteQuestion(Question $question): bool
    {
        return DB::transaction(function () use ($question) {
            $question->answers()->delete();

            $question->choices()->delete();

            return $question->delete();
        });
    }

    /**
     * Supprimer explicitement un choix
     */
    public function deleteChoice(\App\Models\Choice $choice): bool
    {
        return DB::transaction(function () use ($choice) {
            $choice->answers()->delete();

            return $choice->delete();
        });
    }

    /**
     * Supprimer un examen
     */
    public function deleteExam(Exam $exam): bool
    {
        return DB::transaction(function () use ($exam) {

            $exam->questions()->each(function ($question) {
                $question->choices()->delete();
                $question->answers()->delete();
                $question->delete();
            });

            $exam->assignments()->delete();

            return $exam->delete();
        });
    }

    /**
     * Créer les questions pour un examen
     */
    private function createQuestions(Exam $exam, array $questionsData): void
    {
        foreach ($questionsData as $questionData) {
            $question = $exam->questions()->create([
                'content' => $questionData['content'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'order_index' => $questionData['order_index'] ?? 0,
            ]);

            $this->createChoicesForQuestion($question, $questionData);
        }
    }

    /**
     * Mettre à jour les questions de manière intelligente (préserve les questions existantes)
     */
    private function updateQuestions(Exam $exam, array $questionsData): void
    {

        DB::transaction(function () use ($exam, $questionsData) {
            $submittedQuestionIds = [];
            $questionsToUpdate = [];
            $questionsToCreate = [];


            foreach ($questionsData as $questionData) {
                if (isset($questionData['id']) && !empty($questionData['id'])) {

                    $questionsToUpdate[$questionData['id']] = [
                        'content' => $questionData['content'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                        'order_index' => $questionData['order_index'] ?? 0,
                        'updated_at' => now(),
                        'choices_data' => $questionData
                    ];
                    $submittedQuestionIds[] = $questionData['id'];
                } else {
                    $questionsToCreate[] = [
                        'exam_id' => $exam->id,
                        'content' => $questionData['content'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                        'order_index' => $questionData['order_index'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'choices_data' => $questionData
                    ];
                }
            }

            if (!empty($questionsToUpdate)) {
                foreach ($questionsToUpdate as $questionId => $updateData) {
                    $choicesData = $updateData['choices_data'];
                    unset($updateData['choices_data']);

                    Question::where('id', $questionId)
                        ->where('exam_id', $exam->id)
                        ->update($updateData);

                    $question = Question::find($questionId);
                    if ($question) {
                        $this->updateChoicesForQuestion($question, $choicesData);
                    }
                }
            }

            if (!empty($questionsToCreate)) {
                foreach ($questionsToCreate as $questionData) {
                    $choicesData = $questionData['choices_data'];
                    unset($questionData['choices_data']);

                    $question = Question::create($questionData);
                    $this->createChoicesForQuestion($question, $choicesData);
                    $submittedQuestionIds[] = $question->id;
                }
            }
        });
    }

    /**
     * Mettre à jour les choix d'une question existante
     */
    private function updateChoicesForQuestion(Question $question, array $questionData): void
    {
        switch ($questionData['type']) {
            case 'multiple':
            case 'one_choice':
                $this->updateChoiceOptions($question, $questionData);
                break;

            case 'boolean':
                $this->updateBooleanOptions($question, $questionData);
                break;

            case 'text':
                $choiceIds = $question->choices()->pluck('id')->toArray();
                if (!empty($choiceIds)) {

                    Answer::whereIn('choice_id', $choiceIds)->delete();

                    $question->choices()->delete();
                }
                break;
        }
    }

    /**
     * Mettre à jour les choix pour une question à choix multiples ou choix unique
     */
    private function updateChoiceOptions(Question $question, array $questionData): void
    {
        if (!isset($questionData['choices']) || !is_array($questionData['choices'])) {
            return;
        }

        $choicesToUpdate = [];
        $choicesToCreate = [];
        $submittedChoiceIds = [];

        foreach ($questionData['choices'] as $index => $choiceData) {
            $isCorrect = isset($choiceData['is_correct']) ? (bool) $choiceData['is_correct'] : false;

            if (isset($choiceData['id']) && !empty($choiceData['id'])) {
                $choicesToUpdate[$choiceData['id']] = [
                    'content' => $choiceData['content'],
                    'is_correct' => $isCorrect,
                    'order_index' => $choiceData['order_index'] ?? $index,
                    'updated_at' => now(),
                ];
                $submittedChoiceIds[] = $choiceData['id'];
            } else {
                $choicesToCreate[] = [
                    'question_id' => $question->id,
                    'content' => $choiceData['content'],
                    'is_correct' => $isCorrect,
                    'order_index' => $choiceData['order_index'] ?? $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($choicesToUpdate)) {
            foreach ($choicesToUpdate as $choiceId => $updateData) {
                \App\Models\Choice::where('id', $choiceId)
                    ->where('question_id', $question->id)
                    ->update($updateData);
            }
        }

        if (!empty($choicesToCreate)) {
            \App\Models\Choice::insert($choicesToCreate);
        }
    }

    /**
     * Mettre à jour les choix pour une question boolean
     */
    private function updateBooleanOptions(Question $question, array $questionData): void
    {
        $correctAnswer = 'true';
        $submittedChoices = [];

        if (isset($questionData['choices']) && is_array($questionData['choices'])) {
            foreach ($questionData['choices'] as $choice) {
                $submittedChoices[$choice['content']] = $choice;
                if (isset($choice['is_correct']) && $choice['is_correct']) {
                    $correctAnswer = $choice['content'] ?? 'true';
                }
            }
        }

        $existingChoices = $question->choices()->get()->keyBy('content');

        if ($existingChoices->has('true')) {
            $trueChoice = $existingChoices->get('true');
            $trueChoice->update([
                'is_correct' => $correctAnswer === 'true',
                'order_index' => isset($submittedChoices['true']) ?
                    ($submittedChoices['true']['order_index'] ?? 0) : 0,
            ]);
        } else {
            $question->choices()->create([
                'content' => 'true',
                'is_correct' => $correctAnswer === 'true',
                'order_index' => isset($submittedChoices['true']) ?
                    ($submittedChoices['true']['order_index'] ?? 0) : 0,
            ]);
        }

        if ($existingChoices->has('false')) {
            $falseChoice = $existingChoices->get('false');
            $falseChoice->update([
                'is_correct' => $correctAnswer === 'false',
                'order_index' => isset($submittedChoices['false']) ?
                    ($submittedChoices['false']['order_index'] ?? 1) : 1,
            ]);
        } else {
            $question->choices()->create([
                'content' => 'false',
                'is_correct' => $correctAnswer === 'false',
                'order_index' => isset($submittedChoices['false']) ?
                    ($submittedChoices['false']['order_index'] ?? 1) : 1,
            ]);
        }

        $invalidChoiceIds = $question->choices()
            ->whereNotIn('content', ['true', 'false'])
            ->pluck('id')
            ->toArray();

        if (!empty($invalidChoiceIds)) {
            Answer::whereIn('choice_id', $invalidChoiceIds)->delete();

            $question->choices()
                ->whereNotIn('content', ['true', 'false'])
                ->delete();
        }
    }

    /**
     * Creates choice options for the given question based on the provided data.
     * @param Question $question The question entity to which choice options will be added.
     * @param array $questionData The data used to generate the choice options.
     * 
     * @return void
     */
    private function createChoicesForQuestion(Question $question, array $questionData): void
    {
        switch ($questionData['type']) {
            case 'multiple':
            case 'one_choice':
                $this->createChoiceOptions($question, $questionData);
                break;

            case 'boolean':
                $this->createBooleanOptions($question, $questionData);
                break;

            case 'text':
                break;
        }
    }

    /**
     * Creates choice options for the given question based on the provided data.
     * @param Question $question The question entity to which choice options will be added.
     * @param array $questionData The data used to generate the choice options.
     * 
     * @return void
     */
    private function createChoiceOptions(Question $question, array $questionData): void
    {
        if (!isset($questionData['choices']) || !is_array($questionData['choices'])) {
            return;
        }

        $choicesToCreate = [];

        foreach ($questionData['choices'] as $index => $choiceData) {
            $isCorrect = false;

            if (isset($choiceData['is_correct'])) {
                $isCorrect = (bool) $choiceData['is_correct'];
            }

            $choicesToCreate[] = [
                'question_id' => $question->id,
                'content' => $choiceData['content'],
                'is_correct' => $isCorrect,
                'order_index' => $choiceData['order_index'] ?? $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($choicesToCreate)) {
            Choice::insert($choicesToCreate);
        }
    }

    /**
     * Creates boolean options for the given question based on the provided data.
     *
     * @param Question $question The question entity to which boolean options will be added.
     * @param array $questionData The data used to generate the boolean options.
     *
     * @return void
     */
    private function createBooleanOptions(Question $question, array $questionData): void
    {
        $correctAnswer = 'true';

        if (isset($questionData['choices']) && is_array($questionData['choices'])) {
            foreach ($questionData['choices'] as $choice) {
                if (isset($choice['is_correct']) && $choice['is_correct']) {
                    $correctAnswer = $choice['content'] ?? 'true';
                    break;
                }
            }
        }

        $booleanChoices = [
            [
                'question_id' => $question->id,
                'content' => 'true',
                'is_correct' => $correctAnswer === 'true',
                'order_index' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => $question->id,
                'content' => 'false',
                'is_correct' => $correctAnswer === 'false',
                'order_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        Choice::insert($booleanChoices);
    }

    /**
     * Retrieves a paginated list of exams associated with a specific teacher.
     *
     * @param int $teacherId The unique identifier of the teacher.
     * @param int $perPage The number of exams to display per page. Defaults to 10.
     * @param bool|null $status Optional filter to retrieve exams based on their active status. If null, no status filtering is applied.
     * @param string|null $search Optional search term to filter exams by title. If null
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated list of exams for the teacher.
     */
    /**
     * Obtenir les examens d'un professeur avec eager loading optimisé
     * 
     * @param int $teacherId ID du professeur
     * @param int $perPage Nombre d'éléments par page
     * @param bool|null $status Filtre sur le statut (actif/inactif)
     * @param string|null $search Recherche sur titre/description
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTeacherExams(int $teacherId, int $perPage = 10, ?bool $status = null, ?string $search = null)
    {
        return Exam::where('teacher_id', $teacherId)
            ->withCount(['questions', 'assignments'])
            ->latest()
            ->when($search, fn($query) => $query->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"))
            ->when($status !== null, fn($query) => $query->where('is_active', $status))
            ->paginate($perPage);
    }

    /**
     * Creates and returns a duplicate of the given Exam instance.
     *
     * This method copies the properties and related data from the original Exam,
     * generating a new Exam object with the same attributes. The duplicated exam
     * is persisted and returned.
     *
     * @param Exam $originalExam The original Exam instance to duplicate.
     * @return Exam The newly created duplicated Exam instance.
     */
    public function duplicateExam(Exam $originalExam): Exam
    {
        return DB::transaction(function () use ($originalExam) {

            $examData = $originalExam->toArray();
            unset($examData['id'], $examData['created_at'], $examData['updated_at']);
            $examData['title'] = $examData['title'] . ' (Copie)';
            $examData['is_active'] = false;

            $newExam = Exam::create($examData);

            foreach ($originalExam->questions as $originalQuestion) {

                $questionData = $originalQuestion->toArray();

                unset($questionData['id'], $questionData['exam_id'], $questionData['created_at'], $questionData['updated_at']);

                $newQuestion = $newExam->questions()->create($questionData);

                foreach ($originalQuestion->choices as $originalChoice) {
                    $choiceData = $originalChoice->toArray();
                    unset($choiceData['id'], $choiceData['question_id'], $choiceData['created_at'], $choiceData['updated_at']);

                    $newQuestion->choices()->create($choiceData);
                }
            }

            return $newExam->load(['questions.choices']);
        });
    }

    /**
     * Toggles the status of the given Exam instance.
     *
     * This method switches the current status of the provided Exam object
     * (e.g., from active to inactive or vice versa) and returns the updated Exam.
     *
     * @param Exam $exam The Exam instance whose status will be toggled.
     * @return Exam The updated Exam instance with the toggled status.
     */
    public function toggleExamStatus(Exam $exam): Exam
    {
        $exam->is_active = !$exam->is_active;
        $exam->save();

        return $exam;
    }

    /**
     * Retrieves the exams assigned to a specific student.
     * Now includes exams assigned via groups.
     *
     * @param User $student The student user for whom to retrieve assigned exams.
     * @param int $perPage The number of exams to display per page. Defaults to 10.
     * @param string|null $status Optional filter to retrieve exams based on their status. If null, no status filtering is applied.
     * @param string|null $search Optional search term to filter exams by their title or description. If null, no search filtering is applied.
     *
     * @return mixed The list of exams assigned to the student.
     */
    public function getAssignedExamsForStudent(User $student, ?int $perPage = 10, ?string $status = null, ?string $search = null)
    {
        // Vérifier si les groupes avec exams sont déjà chargés
        // Si oui, les utiliser directement. Sinon, les charger.
        if (!$student->relationLoaded('groups')) {
            // Cas où les groupes ne sont pas encore chargés (rare maintenant)
            $student->load(['groups' => function ($query) {
                $query->with(['exams' => function ($q) {
                    $q->where('is_active', true);
                }])
                    ->withPivot(['enrolled_at', 'left_at', 'is_active']);
            }]);
        }

        // Utiliser les groupes déjà chargés et filtrer les actifs EN PHP
        // Pas de nouvelle requête SQL ici
        $examIdsFromGroups = $student->groups
            ->filter(fn($group) => $group->pivot && $group->pivot->is_active)
            ->flatMap(function ($group) {
                // Vérifier si les exams sont chargés
                if (!$group->relationLoaded('exams')) {
                    // Charger les exams seulement si nécessaire
                    $group->load(['exams' => function ($q) {
                        $q->where('is_active', true);
                    }]);
                }
                // Retourner la collection d'exams (peut être vide)
                return $group->exams ?? collect([]);
            })
            ->pluck('id')
            ->unique()
            ->toArray();

        $assignmentsQuery = $student->examAssignments()
            ->with('exam.questions')
            ->orderBy('assigned_at', 'desc');

        if ($search) {
            $assignmentsQuery->whereHas(
                'exam',
                fn($q) =>
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($status) {
            $assignmentsQuery->where('status', $status);
        }

        $existingAssignments = $assignmentsQuery->get();
        $existingExamIds = $existingAssignments->pluck('exam_id')->toArray();

        // Si pas de pagination, retourner seulement les assignments 
        // existants pour maintenir la compatibilité
        if (!$perPage) {
            return $existingAssignments;
        }

        // Pour la pagination, créer des "pseudo-assignments" 
        // pour les examens disponibles via les groupes
        $availableExamIds = array_diff($examIdsFromGroups, $existingExamIds);

        if (!empty($availableExamIds)) {
            $availableExams = Exam::whereIn('id', $availableExamIds)
                ->where('is_active', true)
                ->with('questions')
                ->when(
                    $search,
                    fn($q) =>
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                )
                ->get();

            // Créer des objets ExamAssignment "virtuels" pour les examens disponibles
            $virtualAssignments = $availableExams->map(function ($exam) use ($student) {
                $assignment = new ExamAssignment();
                $assignment->exam_id = $exam->id;
                $assignment->student_id = $student->id;
                $assignment->status = 'assigned';
                $assignment->assigned_at = now();

                // Utiliser setRelation au lieu de l'assignation directe
                $assignment->setRelation('exam', $exam);

                // Marquer comme non-persisté
                $assignment->exists = false;
                return $assignment;
            });

            $allAssignments = $existingAssignments->concat($virtualAssignments);
        } else {
            $allAssignments = $existingAssignments;
        }

        if ($status) {
            $allAssignments = $allAssignments->where('status', $status);
        }

        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $items = $allAssignments->slice($offset, $perPage)->values();
        $total = $allAssignments->count();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Retrieves statistical data for the student dashboard based on the provided exam assignments.
     *
     * @param \Illuminate\Database\Eloquent\Collection $examAssignments 
     * Collection of exam assignments for the student.
     * @return array An associative array containing dashboard statistics.
     */
    public function getStudentDashboardStats(Collection $examAssignments): array
    {
        $totalExams = count($examAssignments);

        $completedExams = $this->filterCompletedAssignments($examAssignments);

        $countCompletedExams = count($completedExams);

        $pendingExams = count($this->filterActiveAssignments($examAssignments));

        $averageScore = $this->calculateAverageScoreForStudent($completedExams);

        return [
            'totalExams' => $totalExams,
            'completedExams' => $countCompletedExams,
            'pendingExams' => $pendingExams,
            'averageScore' => $averageScore,
        ];
    }

    /**
     * Calculate the exam score out of 20.
     *
     * EXAMPLE: if the student has 15/30, the score out of 20 will be (15/30)*20 = 10
     *
     * @param float|null $score The score obtained by the student
     * @param int|null $totalPoints The total possible score for the exam
     *
     * @return float The calculated score out of 20
     */
    public function calculateScoreOutOf20(?float $score, ?int $totalPoints): float
    {
        if ($score === null || $totalPoints === null || $totalPoints === 0) {
            return 0.0;
        }

        return round(($score / $totalPoints) * 20, 2);
    }

    /**
     * Calculates the average score for a student based on their exam assignments.
     *
     * @param \Illuminate\Database\Eloquent\Collection $examAssignments A collection of exam assignment models for the student.
     * @return float The calculated average score.
     */
    public function calculateAverageScoreForStudent(\Illuminate\Database\Eloquent\Collection $examAssignments): float
    {
        $score = 0.0;

        $totalScore = $examAssignments->whereNotNull('score')->sum('score');

        $totalPossible = $examAssignments->sum(function ($assignment) {
            return $assignment->exam && $assignment->exam->questions
                ? $assignment->exam->questions->sum('points')
                : 0;
        });

        if ($totalPossible > 0) {
            $score = ($totalScore / $totalPossible) * 20;
        }

        return round($score, 2);
    }

    /**
     * Retrieves the assigned exam details for a specific student.
     *
     * @param Exam $exam The exam instance to retrieve information for.
     * @param int $studentId The unique identifier of the student.
     * @return mixed The assigned exam details for the student, or null if not found.
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAssignedExamForStudent(Exam $exam, int $studentId)
    {
        return $exam->assignments()
            ->where('student_id', $studentId)
            ->orderBy('assigned_at', 'desc')
            ->firstOrFail();
    }

    /**
     * Retrieves the started exam instance for a specific student.
     *
     * @param Exam $exam The exam object to retrieve for the student.
     * @param int $studentId The ID of the student.
     * @return mixed The started exam instance for the student, or null if not found.
     */
    public function getStartedExamForStudent(Exam $exam, int $studentId)
    {
        return $exam->assignments()
            ->where('student_id', $studentId)
            ->where('status', 'started')
            ->firstOrFail();
    }

    /**
     * Récupérer un assignment complété pour un étudiant spécifique.
     *
     * @param Exam $exam L'objet examen à récupérer pour l'étudiant.
     * @param int $studentId L'ID de l'étudiant.
     * @return mixed L'assignment complété pour l'étudiant, ou null s'il n'est pas trouvé.
     */
    public function getCompletedAssignmentForStudent(Exam $exam, int $studentId)
    {
        return $exam->assignments()
            ->where('student_id', $studentId)
            ->whereIn('status', self::EXAM_COMPLETED_STATUSES)
            ->firstOrFail();
    }
}
