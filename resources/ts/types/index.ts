export * from './models';
export * from './models/shared';
export * from './datatable';

export type FlashMessageObject = { id: string; message: string } | null;

export interface FlashMessages {
    success?: FlashMessageObject;
    error?: FlashMessageObject;
    warning?: FlashMessageObject;
    info?: FlashMessageObject;
}

import type { User } from './models/shared/user';

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
        canAssignPermissions: boolean;;
    };
    flash: FlashMessages;
    locale: string;
} & T;

export * from './api';
