export interface ClassRouteContext {
    role: 'admin' | 'teacher';
    indexRoute: string;
    showRoute: string;
    editRoute: string | null;
    deleteRoute: string | null;
    assessmentsRoute: string | null;
    subjectShowRoute: string | null;
    studentShowRoute: string | null;
    studentIndexRoute: string | null;
    studentAssignmentsRoute: string | null;
    assessmentShowRoute: string | null;
    assessmentGradeRoute: string | null;
    assessmentReviewRoute: string | null;
    assessmentSaveGradeRoute: string | null;
    resultsRoute?: string | null;
}

export interface AssessmentRouteContext {
    role: 'admin' | 'teacher';
    backRoute: string;
    showRoute: string | null;
    classAssessmentShowRoute: string | null;
    reviewRoute: string;
    gradeRoute: string;
    saveGradeRoute: string;
    editRoute?: string | null;
    publishRoute?: string | null;
    unpublishRoute?: string | null;
    duplicateRoute?: string | null;
    reopenRoute?: string | null;
    reassignRoute?: string | null;
    createRoute?: string | null;
}
