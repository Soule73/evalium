<?php

namespace App\Services\Student;

use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;

class StudentExamAccessService
{
    /**
     * Check if student is member of the given group
     *
     * @param Group $group
     * @param User $student
     * @return Model|null Returns pivot model with is_active status or null
     */
    public function getStudentGroupMembership(Group $group, User $student): ?Model
    {
        return $group->students()
            ->wherePivot('student_id', $student->id)
            ->first(['group_student.is_active']);
    }

    /**
     * Get the group associated with an exam for a student
     *
     * @param Exam $exam
     * @param User $student
     * @return Model|null
     */
    public function getStudentGroupForExam(Exam $exam, User $student): ?Model
    {
        return $student->groups()
            ->select('groups.id', 'groups.level_id')
            ->whereHas('exams', function ($query) use ($exam) {
                $query->where('exams.id', $exam->id);
            })
            ->with('level:id,name')
            ->first();
    }

    /**
     * Check if the student's group membership is active
     *
     * @param Model|null $studentPivot
     * @return bool
     */
    public function isActiveGroupMembership(?Model $studentPivot): bool
    {
        if (!$studentPivot) {
            return false;
        }

        return (bool) $studentPivot->pivot->is_active;
    }

    /**
     * Load group with level relation
     *
     * @param Group $group
     * @return void
     */
    public function loadGroupLevel(Group $group): void
    {
        if (!$group->relationLoaded('level')) {
            $group->load('level');
        }
    }

    /**
     * Load exam with teacher relation
     *
     * @param Exam $exam
     * @return void
     */
    public function loadExamTeacher(Exam $exam): void
    {
        if (!$exam->relationLoaded('teacher')) {
            $exam->load('teacher');
        }
    }

    /**
     * Load exam with questions and choices
     *
     * @param Exam $exam
     * @return void
     */
    public function loadExamQuestionsWithChoices(Exam $exam): void
    {
        if (!$exam->relationLoaded('questions')) {
            $exam->load(['questions.choices']);
        }
    }

    /**
     * Get questions count for an exam
     *
     * @param Exam $exam
     * @return int
     */
    public function getQuestionsCount(Exam $exam): int
    {
        if ($exam->relationLoaded('questions')) {
            return $exam->questions->count();
        }

        return $exam->questions()->count();
    }

    /**
     * Check if exam has text questions
     *
     * @param Exam $exam
     * @return bool
     */
    public function hasTextQuestions(Exam $exam): bool
    {
        if ($exam->relationLoaded('questions')) {
            return $exam->questions->contains('type', 'text');
        }

        return $exam->questions()->where('type', 'text')->exists();
    }

    /**
     * Load assignment with answers and choices
     *
     * @param \App\Models\ExamAssignment $assignment
     * @return void
     */
    public function loadAssignmentAnswers($assignment): void
    {
        if (!$assignment->relationLoaded('answers')) {
            $assignment->load(['answers.choice']);
        }
    }
}
