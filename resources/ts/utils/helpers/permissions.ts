/**
 * Helper functions pour vérifier les permissions côté frontend
 */

/**
 * Vérifie si l'utilisateur a une permission spécifique
 */
export const hasPermission = (permissions: string[], permission: string): boolean => {
    return permissions.includes(permission);
};

/**
 * Vérifie si l'utilisateur a toutes les permissions spécifiées
 */
export const hasAllPermissions = (permissions: string[], requiredPermissions: string[]): boolean => {
    return requiredPermissions.every(permission => permissions.includes(permission));
};

/**
 * Vérifie si l'utilisateur a au moins une des permissions spécifiées
 */
export const hasAnyPermission = (permissions: string[], requiredPermissions: string[]): boolean => {
    return requiredPermissions.some(permission => permissions.includes(permission));
};

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
export const hasRole = (roles: string[], role: string): boolean => {
    return roles.includes(role);
};

/**
 * Vérifie si l'utilisateur a tous les rôles spécifiés
 */
export const hasAllRoles = (roles: string[], requiredRoles: string[]): boolean => {
    return requiredRoles.every(role => roles.includes(role));
};

/**
 * Vérifie si l'utilisateur a au moins un des rôles spécifiés
 */
export const hasAnyRole = (roles: string[], requiredRoles: string[]): boolean => {
    return requiredRoles.some(role => roles.includes(role));
};

/**
 * Liste complète des permissions disponibles dans l'application
 */
export const PERMISSIONS = {
    // User permissions
    VIEW_USERS: 'view users',
    CREATE_USERS: 'create users',
    UPDATE_USERS: 'update users',
    DELETE_USERS: 'delete users',
    RESTORE_USERS: 'restore users',
    FORCE_DELETE_USERS: 'force delete users',
    MANAGE_STUDENTS: 'manage students',

    // Exam permissions
    VIEW_EXAMS: 'view exams',
    VIEW_ANY_EXAMS: 'view any exams',
    CREATE_EXAMS: 'create exams',
    UPDATE_EXAMS: 'update exams',
    DELETE_EXAMS: 'delete exams',
    RESTORE_EXAMS: 'restore exams',
    FORCE_DELETE_EXAMS: 'force delete exams',
    ASSIGN_EXAMS: 'assign exams',
    CORRECT_EXAMS: 'correct exams',
    VIEW_EXAM_RESULTS: 'view exam results',

    // Level permissions
    VIEW_LEVELS: 'view levels',
    CREATE_LEVELS: 'create levels',
    UPDATE_LEVELS: 'update levels',
    DELETE_LEVELS: 'delete levels',

    // Role management
    VIEW_ROLES: 'view roles',
    CREATE_ROLES: 'create roles',
    UPDATE_ROLES: 'update roles',
    DELETE_ROLES: 'delete roles',
} as const;

/**
 * Liste des rôles disponibles
 */
export const ROLES = {
    SUPER_ADMIN: 'super_admin',
    ADMIN: 'admin',
    TEACHER: 'teacher',
    STUDENT: 'student',
} as const;
