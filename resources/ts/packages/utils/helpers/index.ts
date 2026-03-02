export { navRoutes } from './navRoutes';
export {
    hasPermission,
    hasAllPermissions,
    hasAnyPermission,
    hasRole,
    hasAllRoles,
    hasAnyRole,
    PERMISSIONS,
    ROLES,
} from './permissions';
export { setupZiggy } from './ziggy';
export { translateKey } from './translations';
export type { LanguageData } from './translations';
export { getAcademicYearStatus } from './academicYear';
export type { AcademicYearStatusType } from './academicYear';
export {
    buildDataTableUrl,
    getSelectableItems,
    areAllSelectableItemsSelected,
    areSomeSelectableItemsSelected,
    canAddToSelection,
    toggleAllPageSelection,
    selectAllItems,
    toggleItemSelection,
} from './dataTableUtils';
