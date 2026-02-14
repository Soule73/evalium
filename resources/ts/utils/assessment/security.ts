/**
 * Helpers pour les mesures de sécurité lors de la passation d'une évaluation
 * Fournit des fonctions pour gérer les événements de sécurité et appliquer des protections contre les tricheries
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

/**
 * Types de violations de sécurité reconnus
 */
export const VIOLATION_TYPES = {
    TAB_SWITCH: 'tab_switch',
    FULLSCREEN_EXIT: 'fullscreen_exit',
} as const;

/**
 * Gestionnaire pour l'événement beforeunload
 * Empêche la fermeture/navigation de la page
 */
export const createBeforeUnloadHandler = () => {
    return (e: BeforeUnloadEvent) => {
        e.preventDefault();
        return '';
    };
};

/**
 * Vérifie si une combinaison de touches est interdite pour les outils de développement
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
 * Vérifie si une combinaison de touches est interdite pour le rechargement
 */
export const isReloadShortcut = (e: KeyboardEvent): boolean => {
    return (
        ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R')) ||
        e.key === 'F5'
    );
};

/**
 * Vérifie si une combinaison de touches est interdite pour copier/coller
 */
export const isCopyPasteShortcut = (e: KeyboardEvent): boolean => {
    return (
        e.ctrlKey && (
            e.key === 'c' || e.key === 'C' ||
            e.key === 'v' || e.key === 'V' ||
            e.key === 'x' || e.key === 'X' ||
            e.key === 'a' || e.key === 'A'
        )
    );
};

/**
 * Vérifie si une combinaison de touches est interdite pour l'impression
 */
export const isPrintShortcut = (e: KeyboardEvent): boolean => {
    return (e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P');
};

/**
 * Vérifie si une combinaison de touches est interdite pour le zoom
 */
export const isZoomShortcut = (e: KeyboardEvent): boolean => {
    return e.ctrlKey && (e.key === '+' || e.key === '-' || e.key === '0');
};

/**
 * Vérifie si une combinaison de touches est interdite pour les nouvelles fenêtres/onglets
 */
export const isNewWindowShortcut = (e: KeyboardEvent): boolean => {
    return (
        e.ctrlKey && (
            e.key === 't' || e.key === 'T' ||
            e.key === 'n' || e.key === 'N'
        )
    );
};

/**
 * Vérifie si la touche Escape est pressée
 */
export const isEscapeKey = (e: KeyboardEvent): boolean => {
    return e.key === 'Escape';
};

/**
 * Gestionnaire principal pour les événements clavier
 * Bloque les combinaisons de touches selon la configuration fournie
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
        blockEscape = true
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
 * Gestionnaire pour empêcher la sélection de texte
 */
export const createSelectStartHandler = () => {
    return (e: Event) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Gestionnaire pour empêcher le menu contextuel
 */
export const createContextMenuHandler = () => {
    return (e: MouseEvent) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Gestionnaire pour empêcher le glisser-déposer
 */
export const createDragHandler = () => {
    return (e: DragEvent) => {
        e.preventDefault();
        return false;
    };
};

/**
 * Gestionnaire pour empêcher l'impression
 */
export const createPrintHandler = () => {
    return () => false;
};

/**
 * Gestionnaire pour empêcher les actions sur les images et liens
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
 * Configuration des styles CSS pour empêcher la sélection
 */
export const disableTextSelection = (): void => {
    document.body.style.userSelect = 'none';
    (document.body.style as unknown as Record<string, string>).mozUserSelect = 'none';
};

/**
 * Restauration des styles CSS pour permettre la sélection
 */
export const enableTextSelection = (): void => {
    document.body.style.userSelect = '';
    (document.body.style as unknown as Record<string, string>).mozUserSelect = '';
};

/**
 * Restaure les barres de défilement
 */
export const showScrollbars = (): void => {
    document.documentElement.style.overflow = '';
};

/**
 * Applique toutes les protections de sécurité
 */
export const applySecurityMeasures = (): SecurityEventHandlers => {
    const handlers: SecurityEventHandlers = {
        handleBeforeUnload: createBeforeUnloadHandler(),
        handleKeyDown: createKeyDownHandler(),
        handleSelectStart: createSelectStartHandler(),
        handleContextMenu: createContextMenuHandler(),
        handleDragStart: createDragHandler(),
        handlePrint: createPrintHandler(),
        preventImageActions: createImageActionsHandler()
    };

    disableTextSelection();

    return handlers;
};

/**
 * Supprime toutes les protections de sécurité
 */
export const removeSecurityMeasures = (): void => {
    enableTextSelection();
    showScrollbars();
};

/**
 * Attache tous les gestionnaires d'événements
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
 * Détache tous les gestionnaires d'événements
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
 * Crée des gestionnaires de sécurité configurables selon les fonctionnalités activées
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
            blockEscape: false
        }),
        handleSelectStart: config.copyPastePrevention ? createSelectStartHandler() : () => true,
        handleContextMenu: config.contextMenuDisabled ? createContextMenuHandler() : () => true,
        handleDragStart: config.copyPastePrevention ? createDragHandler() : () => true,
        handlePrint: config.printPrevention ? createPrintHandler() : () => true,
        preventImageActions: config.copyPastePrevention ? createImageActionsHandler() : () => true
    };

    return handlers;
};

/**
 * Applique des mesures de sécurité configurables
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
 * Attache les gestionnaires d'événements configurables
 */
export const attachConfigurableEventListeners = (
    handlers: SecurityEventHandlers,
    config: {
        devToolsDetection?: boolean;
        copyPastePrevention?: boolean;
        contextMenuDisabled?: boolean;
        printPrevention?: boolean;
        tabSwitchDetection?: boolean;
    }
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
 * Détache les gestionnaires d'événements configurables
 */
export const detachConfigurableEventListeners = (
    handlers: SecurityEventHandlers,
    config: {
        devToolsDetection?: boolean;
        copyPastePrevention?: boolean;
        contextMenuDisabled?: boolean;
        printPrevention?: boolean;
        tabSwitchDetection?: boolean;
    }
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
