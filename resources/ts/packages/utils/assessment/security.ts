/**
 * Security measure helpers for assessment completion.
 * Provides functions to manage security events and apply protections against cheating.
 */
export interface SecurityEventHandlers {
    handleBeforeUnload: (e: BeforeUnloadEvent) => void;
    handleKeyDown: (e: KeyboardEvent) => void;
    handleSelectStart: (e: Event) => boolean;
    handleContextMenu: (e: MouseEvent) => boolean;
    handleDragStart: (e: DragEvent) => boolean;
    handlePrint: () => boolean;
    preventImageActions: (e: Event) => boolean;
}

export interface AssessmentConfig {
    devMode: boolean;
}

/**
 * Checks if security measures are enabled based on the assessment config.
 */
export function isSecurityEnabled(config: AssessmentConfig): boolean {
    return !config.devMode;
}

/**
 * Checks if a specific security feature is enabled.
 */
export function isFeatureEnabled(config: AssessmentConfig, _feature: string): boolean {
    return !config.devMode;
}

/**
 * Recognized security violation types
 */
export const VIOLATION_TYPES = {
    TAB_SWITCH: 'tab_switch',
    FULLSCREEN_EXIT: 'fullscreen_exit',
} as const;

/**
 * Handler for the beforeunload event.
 * Prevents page closure and navigation.
 */
export const createBeforeUnloadHandler = () => {
    return (e: BeforeUnloadEvent) => {
        e.preventDefault();
        return '';
    };
};

/**
 * Checks if a key combination is forbidden for developer tools.
 */
export const isDeveloperToolsShortcut = (e: KeyboardEvent): boolean => {
    return (
        e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) ||
        (e.ctrlKey && e.shiftKey && (e.key === 'J' || e.key === 'j')) ||
        (e.ctrlKey && e.shiftKey && (e.key === 'C' || e.key === 'c')) ||
        (e.ctrlKey && (e.key === 'U' || e.key === 'u'))
    );
};

/**
 * Checks if a key combination is forbidden for page reload.
 */
export const isReloadShortcut = (e: KeyboardEvent): boolean => {
    return ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R')) || e.key === 'F5';
};

/**
 * Checks if a key combination is forbidden for copy/paste.
 */
export const isCopyPasteShortcut = (e: KeyboardEvent): boolean => {
    return (
        e.ctrlKey &&
        (e.key === 'c' ||
            e.key === 'C' ||
            e.key === 'v' ||
            e.key === 'V' ||
            e.key === 'x' ||
            e.key === 'X' ||
            e.key === 'a' ||
            e.key === 'A')
    );
};

/**
 * Checks if a key combination is forbidden for printing.
 */
export const isPrintShortcut = (e: KeyboardEvent): boolean => {
    return (e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P');
};

/**
 * Checks if a key combination is forbidden for zooming.
 */
export const isZoomShortcut = (e: KeyboardEvent): boolean => {
    return e.ctrlKey && (e.key === '+' || e.key === '-' || e.key === '0');
};

/**
 * Checks if a key combination is forbidden for opening new windows/tabs.
 */
export const isNewWindowShortcut = (e: KeyboardEvent): boolean => {
    return e.ctrlKey && (e.key === 't' || e.key === 'T' || e.key === 'n' || e.key === 'N');
};

/**
 * Checks if the Escape key is pressed.
 */
export const isEscapeKey = (e: KeyboardEvent): boolean => {
    return e.key === 'Escape';
};

/**
 * Main handler for keyboard events.
 * Blocks key combinations according to the provided configuration.
 */
export const createKeyDownHandler = (config?: {
    blockDevTools?: boolean;
    blockReload?: boolean;
    blockCopyPaste?: boolean;
    blockPrint?: boolean;
    blockZoom?: boolean;
    blockNewWindow?: boolean;
    blockEscape?: boolean;
}) => {
    const {
        blockDevTools = true,
        blockReload = true,
        blockCopyPaste = true,
        blockPrint = true,
        blockZoom = true,
        blockNewWindow = true,
        blockEscape = true,
    } = config || {};

    return (e: KeyboardEvent) => {
        if (blockDevTools && isDeveloperToolsShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockReload && isReloadShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockCopyPaste && isCopyPasteShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockPrint && isPrintShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockZoom && isZoomShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockNewWindow && isNewWindowShortcut(e)) {
            e.preventDefault();
            return false;
        }
        if (blockEscape && isEscapeKey(e)) {
            e.preventDefault();
            return false;
        }
    };
};

/**
 * Handler to prevent text selection.
 */
export const createSelectStartHandler = () => {
    return (e: Event) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Handler to prevent context menu.
 */
export const createContextMenuHandler = () => {
    return (e: MouseEvent) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Handler to prevent drag and drop.
 */
export const createDragHandler = () => {
    return (e: DragEvent) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Handler to prevent printing.
 */
export const createPrintHandler = () => {
    return () => false;
};

/**
 * Handler to prevent actions on images and links.
 */
export const createImageActionsHandler = () => {
    return (e: Event) => {
        const target = e.target as HTMLElement;
        if (target.tagName === 'IMG' || target.tagName === 'A') {
            e.preventDefault();
            return false;
        }
        return true;
    };
};

/**
 * CSS style configuration to prevent text selection.
 */
export const disableTextSelection = (): void => {
    document.body.style.userSelect = 'none';
    (document.body.style as unknown as Record<string, string>).mozUserSelect = 'none';
};

/**
 * Restores CSS styles to allow text selection.
 */
export const enableTextSelection = (): void => {
    document.body.style.userSelect = '';
    (document.body.style as unknown as Record<string, string>).mozUserSelect = '';
};

/**
 * Restores scrollbars.
 */
export const showScrollbars = (): void => {
    document.documentElement.style.overflow = '';
};

/**
 * Applies all security protections.
 */
export const applySecurityMeasures = (): SecurityEventHandlers => {
    const handlers: SecurityEventHandlers = {
        handleBeforeUnload: createBeforeUnloadHandler(),
        handleKeyDown: createKeyDownHandler(),
        handleSelectStart: createSelectStartHandler(),
        handleContextMenu: createContextMenuHandler(),
        handleDragStart: createDragHandler(),
        handlePrint: createPrintHandler(),
        preventImageActions: createImageActionsHandler(),
    };

    disableTextSelection();

    return handlers;
};

/**
 * Removes all security protections.
 */
export const removeSecurityMeasures = (): void => {
    enableTextSelection();
    showScrollbars();
};

/**
 * Attaches all event handlers.
 */
export const attachSecurityEventListeners = (handlers: SecurityEventHandlers): void => {
    window.addEventListener('beforeunload', handlers.handleBeforeUnload);
    document.addEventListener('keydown', handlers.handleKeyDown);
    document.addEventListener('selectstart', handlers.handleSelectStart);
    document.addEventListener('contextmenu', handlers.handleContextMenu);
    document.addEventListener('dragstart', handlers.handleDragStart);
    document.addEventListener('dragover', handlers.handleDragStart);
    document.addEventListener('drop', handlers.handleDragStart);
    document.addEventListener('mousedown', handlers.preventImageActions);
    window.addEventListener('beforeprint', handlers.handlePrint);
};

/**
 * Detaches all event handlers.
 */
export const detachSecurityEventListeners = (handlers: SecurityEventHandlers): void => {
    window.removeEventListener('beforeunload', handlers.handleBeforeUnload);
    document.removeEventListener('keydown', handlers.handleKeyDown);
    document.removeEventListener('selectstart', handlers.handleSelectStart);
    document.removeEventListener('contextmenu', handlers.handleContextMenu);
    document.removeEventListener('dragstart', handlers.handleDragStart);
    document.removeEventListener('dragover', handlers.handleDragStart);
    document.removeEventListener('drop', handlers.handleDragStart);
    document.removeEventListener('mousedown', handlers.preventImageActions);
    window.removeEventListener('beforeprint', handlers.handlePrint);
};

/**
 * Creates configurable security handlers based on enabled features.
 */
export const createConfigurableSecurityHandlers = (config: {
    devToolsDetection?: boolean;
    copyPastePrevention?: boolean;
    contextMenuDisabled?: boolean;
    printPrevention?: boolean;
    tabSwitchDetection?: boolean;
}) => {
    const handlers: SecurityEventHandlers = {
        handleBeforeUnload: createBeforeUnloadHandler(),
        handleKeyDown: createKeyDownHandler({
            blockDevTools: config.devToolsDetection,
            blockCopyPaste: config.copyPastePrevention,
            blockPrint: config.printPrevention,
            blockReload: true,
            blockZoom: false,
            blockNewWindow: false,
            blockEscape: false,
        }),
        handleSelectStart: config.copyPastePrevention ? createSelectStartHandler() : () => true,
        handleContextMenu: config.contextMenuDisabled ? createContextMenuHandler() : () => true,
        handleDragStart: config.copyPastePrevention ? createDragHandler() : () => true,
        handlePrint: config.printPrevention ? createPrintHandler() : () => true,
        preventImageActions: config.copyPastePrevention ? createImageActionsHandler() : () => true,
    };

    return handlers;
};

/**
 * Applies configurable security measures.
 */
export const applyConfigurableSecurityMeasures = (config: {
    devToolsDetection?: boolean;
    copyPastePrevention?: boolean;
    contextMenuDisabled?: boolean;
    printPrevention?: boolean;
    tabSwitchDetection?: boolean;
}): SecurityEventHandlers => {
    const handlers = createConfigurableSecurityHandlers(config);

    if (config.copyPastePrevention) {
        disableTextSelection();
    }

    return handlers;
};

/**
 * Attaches configurable event handlers.
 */
export const attachConfigurableEventListeners = (
    handlers: SecurityEventHandlers,
    config: {
        devToolsDetection?: boolean;
        copyPastePrevention?: boolean;
        contextMenuDisabled?: boolean;
        printPrevention?: boolean;
        tabSwitchDetection?: boolean;
    },
): void => {
    if (config.tabSwitchDetection) {
        window.addEventListener('beforeunload', handlers.handleBeforeUnload);
    }

    if (config.devToolsDetection || config.copyPastePrevention || config.printPrevention) {
        document.addEventListener('keydown', handlers.handleKeyDown);
    }

    if (config.copyPastePrevention) {
        document.addEventListener('selectstart', handlers.handleSelectStart);
        document.addEventListener('dragstart', handlers.handleDragStart);
        document.addEventListener('dragover', handlers.handleDragStart);
        document.addEventListener('drop', handlers.handleDragStart);
        document.addEventListener('mousedown', handlers.preventImageActions);
    }

    if (config.contextMenuDisabled) {
        document.addEventListener('contextmenu', handlers.handleContextMenu);
    }

    if (config.printPrevention) {
        window.addEventListener('beforeprint', handlers.handlePrint);
    }
};

/**
 * Detaches configurable event handlers.
 */
export const detachConfigurableEventListeners = (
    handlers: SecurityEventHandlers,
    config: {
        devToolsDetection?: boolean;
        copyPastePrevention?: boolean;
        contextMenuDisabled?: boolean;
        printPrevention?: boolean;
        tabSwitchDetection?: boolean;
    },
): void => {
    if (config.tabSwitchDetection) {
        window.removeEventListener('beforeunload', handlers.handleBeforeUnload);
    }

    if (config.devToolsDetection || config.copyPastePrevention || config.printPrevention) {
        document.removeEventListener('keydown', handlers.handleKeyDown);
    }

    if (config.copyPastePrevention) {
        document.removeEventListener('selectstart', handlers.handleSelectStart);
        document.removeEventListener('dragstart', handlers.handleDragStart);
        document.removeEventListener('dragover', handlers.handleDragStart);
        document.removeEventListener('drop', handlers.handleDragStart);
        document.removeEventListener('mousedown', handlers.preventImageActions);
    }

    if (config.contextMenuDisabled) {
        document.removeEventListener('contextmenu', handlers.handleContextMenu);
    }

    if (config.printPrevention) {
        window.removeEventListener('beforeprint', handlers.handlePrint);
    }
};
