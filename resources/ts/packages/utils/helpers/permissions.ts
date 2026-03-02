/**
 * Helper functions to check permissions on the frontend
 */

/**
 * Check if the user has a specific permission
 */
export const hasPermission = (permissions: string[], permission: string): boolean => {
    return permissions.includes(permission);
};

/**
 * Check if the user has all of the specified permissions
 */
export const hasAllPermissions = (
    permissions: string[],
    requiredPermissions: string[],
): boolean => {
    return requiredPermissions.every((permission) => permissions.includes(permission));
};

/**
 * Check if the user has at least one of the specified permissions
 */
export const hasAnyPermission = (permissions: string[], requiredPermissions: string[]): boolean => {
    return requiredPermissions.some((permission) => permissions.includes(permission));
};

/**
 * Check if the user has a specific role
 */
export const hasRole = (roles: string[], role: string): boolean => {
    return roles.includes(role);
};

/**
 * Check if the user has all of the specified roles
 */
export const hasAllRoles = (roles: string[], requiredRoles: string[]): boolean => {
    return requiredRoles.every((role) => roles.includes(role));
};

/**
 * Check if the user has at least one of the specified roles
 */
export const hasAnyRole = (roles: string[], requiredRoles: string[]): boolean => {
    return requiredRoles.some((role) => roles.includes(role));
};

/**
 * Available permissions in the application
 */
export const PERMISSIONS = {
    // User Management
    VIEW_USERS: 'view users',
    CREATE_USERS: 'create users',
    UPDATE_USERS: 'update users',
    DELETE_USERS: 'delete users',
    RESTORE_USERS: 'restore users',
    FORCE_DELETE_USERS: 'force delete users',
    MANAGE_STUDENTS: 'manage students',

    // Level Management
    VIEW_LEVELS: 'view levels',
    CREATE_LEVELS: 'create levels',
    UPDATE_LEVELS: 'update levels',
    DELETE_LEVELS: 'delete levels',

    // Role & Permission Management
    VIEW_ROLES: 'view roles',
    CREATE_ROLES: 'create roles',
    UPDATE_ROLES: 'update roles',
    DELETE_ROLES: 'delete roles',

    // Academic Year Management
    VIEW_ACADEMIC_YEARS: 'view academic years',
    CREATE_ACADEMIC_YEARS: 'create academic years',
    UPDATE_ACADEMIC_YEARS: 'update academic years',
    DELETE_ACADEMIC_YEARS: 'delete academic years',
    ARCHIVE_ACADEMIC_YEARS: 'archive academic years',

    // Subject Management
    VIEW_SUBJECTS: 'view subjects',
    CREATE_SUBJECTS: 'create subjects',
    UPDATE_SUBJECTS: 'update subjects',
    DELETE_SUBJECTS: 'delete subjects',

    // Class Management
    VIEW_CLASSES: 'view classes',
    CREATE_CLASSES: 'create classes',
    UPDATE_CLASSES: 'update classes',
    DELETE_CLASSES: 'delete classes',

    // Enrollment Management
    VIEW_ENROLLMENTS: 'view enrollments',
    CREATE_ENROLLMENTS: 'create enrollments',
    UPDATE_ENROLLMENTS: 'update enrollments',
    DELETE_ENROLLMENTS: 'delete enrollments',
    TRANSFER_ENROLLMENTS: 'transfer enrollments',

    // ClassSubject Management
    VIEW_CLASS_SUBJECTS: 'view class subjects',
    CREATE_CLASS_SUBJECTS: 'create class subjects',
    UPDATE_CLASS_SUBJECTS: 'update class subjects',
    DELETE_CLASS_SUBJECTS: 'delete class subjects',
    REPLACE_TEACHER_CLASS_SUBJECTS: 'replace teacher class subjects',

    // Assessment Management
    VIEW_ASSESSMENTS: 'view assessments',
    CREATE_ASSESSMENTS: 'create assessments',
    UPDATE_ASSESSMENTS: 'update assessments',
    DELETE_ASSESSMENTS: 'delete assessments',
    PUBLISH_ASSESSMENTS: 'publish assessments',
    GRADE_ASSESSMENTS: 'grade assessments',
} as const;

/**
 * Available user roles in the application
 */
export const ROLES = {
    SUPER_ADMIN: 'super_admin',
    ADMIN: 'admin',
    TEACHER: 'teacher',
    STUDENT: 'student',
} as const;
