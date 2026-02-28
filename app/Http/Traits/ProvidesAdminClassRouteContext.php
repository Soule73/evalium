<?php

namespace App\Http\Traits;

trait ProvidesAdminClassRouteContext
{
    /**
     * Build the admin route context array shared across class-related views.
     *
     * @return array<string, string>
     */
    protected function adminClassRouteContext(): array
    {
        return [
            'role' => 'admin',
            'indexRoute' => 'admin.classes.index',
            'showRoute' => 'admin.classes.show',
            'editRoute' => 'admin.classes.edit',
            'deleteRoute' => 'admin.classes.destroy',
            'assessmentsRoute' => 'admin.classes.assessments',
            'subjectShowRoute' => 'admin.classes.subjects.show',
            'studentShowRoute' => 'admin.classes.students.show',
            'studentIndexRoute' => 'admin.classes.students.index',
            'studentAssignmentsRoute' => 'admin.classes.students.assignments',
            'assessmentShowRoute' => 'admin.classes.assessments.show',
            'assessmentGradeRoute' => 'admin.assessments.grade',
            'assessmentReviewRoute' => 'admin.assessments.review',
            'assessmentSaveGradeRoute' => 'admin.assessments.saveGrade',
            'resultsRoute' => 'admin.classes.results',
        ];
    }
}
