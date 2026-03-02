import type { User } from './shared/user';

export * from './academicYear';
export * from './assessment';
export * from './assessmentAssignment';
export * from './class';
export * from './classSubject';
export * from './enrollment';
export * from './grades';
export * from './notification';
export * from './semester';
export * from './subject';
export * from './shared';
export * from './datatable';
export * from './route-context';
export * from './question-rendering';

export type FlashMessageObject = { id: string; message: string } | null;

export interface CreatedUserCredentials {
    id: number;
    name: string;
    email: string;
    password: string;
}

export interface FlashMessages {
    success?: FlashMessageObject;
    error?: FlashMessageObject;
    warning?: FlashMessageObject;
    info?: FlashMessageObject;
    has_new_user?: boolean | null;
}

export type PageProps<T = Record<string, unknown>> = {
    auth: {
        user: User;
        permissions: string[];
        roles: string[];
    };
    permissions: {
        canManageLevels: boolean;
        canManageRoles: boolean;
        canManageUsers: boolean;
        canManageAcademicYears: boolean;
        canManageSubjects: boolean;
        canManageClasses: boolean;
        canManageEnrollments: boolean;
        canManageClassSubjects: boolean;
        canManageAssessments: boolean;

        canViewReports: boolean;
        canExportReports: boolean;
        canGradeAnswers: boolean;

        canCreateUsers: boolean;
        canUpdateUsers: boolean;
        canDeleteUsers: boolean;
        canManageStudents: boolean;
        canManageTeachers: boolean;

        canCreateLevels: boolean;
        canUpdateLevels: boolean;
        canDeleteLevels: boolean;

        canCreateRoles: boolean;
        canUpdateRoles: boolean;
        canDeleteRoles: boolean;
        canAssignPermissions: boolean;
    };
    flash: FlashMessages;
    locale: string;
    notifications?: {
        unread_count: number;
    };
} & T;
