export interface AssessmentRouteContext {
    role: 'admin' | 'teacher';
    backRoute: string;
    showRoute: string | null;
    reviewRoute: string;
    gradeRoute: string;
    saveGradeRoute?: string;
    editRoute?: string | null;
    publishRoute?: string | null;
    unpublishRoute?: string | null;
    duplicateRoute?: string | null;
    reopenRoute?: string | null;
}
