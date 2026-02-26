<?php

namespace App\Http\Traits;

trait HasTeacherClassRouteContext
{
    /**
     * Build the shared teacher class route context used across class-scoped controllers.
     *
     * @return array<string, string|null>
     */
    private function buildTeacherClassRouteContext(): array
    {
        return [
            'role' => 'teacher',
            'indexRoute' => 'teacher.classes.index',
            'showRoute' => 'teacher.classes.show',
            'editRoute' => null,
            'deleteRoute' => null,
            'assessmentsRoute' => 'teacher.classes.assessments',
            'subjectShowRoute' => null,
            'studentShowRoute' => 'teacher.classes.students.show',
            'studentIndexRoute' => 'teacher.classes.students.index',
            'studentAssignmentsRoute' => 'teacher.classes.students.assignments',
            'assessmentShowRoute' => 'teacher.classes.assessments.show',
            'assessmentGradeRoute' => 'teacher.assessments.grade',
            'assessmentReviewRoute' => 'teacher.assessments.review',
            'assessmentSaveGradeRoute' => 'teacher.assessments.saveGrade',
        ];
    }
}
