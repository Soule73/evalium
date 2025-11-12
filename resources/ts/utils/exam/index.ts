export {
    formatExamScore,
    calculatePercentage,
    validateScore,
    requiresManualGrading,
    formatScoresForSave,
    getCorrectionStatus,
    hasUserResponse,
    calculateScoreDisplay,
} from './utils';

export {
    QUESTION_TYPE_CONFIG,
    getQuestionTypeIcon
} from './questionTypes';

export type { IconConfig } from './questionTypes';

export {
    createDefaultQuestion,
    createDefaultChoices,
    createBooleanChoices,
    createChoice
} from './questionFactory';

export {
    VIOLATION_TYPES,
    createBeforeUnloadHandler,
    isDeveloperToolsShortcut,
    isReloadShortcut,
    isCopyPasteShortcut,
    isPrintShortcut,
    isZoomShortcut,
    isNewWindowShortcut,
    isEscapeKey,
    createKeyDownHandler,
    createSelectStartHandler,
    createContextMenuHandler,
    createDragHandler,
    createPrintHandler,
    createImageActionsHandler,
    disableTextSelection,
    enableTextSelection,
    showScrollbars,
    applySecurityMeasures,
    removeSecurityMeasures,
    attachSecurityEventListeners,
    detachSecurityEventListeners,
    createConfigurableSecurityHandlers,
    applyConfigurableSecurityMeasures,
    attachConfigurableEventListeners,
    detachConfigurableEventListeners,
} from './security';

export type { SecurityEventHandlers } from './security';

export * from './take';
export * from './components';
