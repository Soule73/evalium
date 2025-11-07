import { useState, useEffect, useCallback } from 'react';
import { useExamConfig, isSecurityEnabled, isFeatureEnabled } from './useExamConfig';

interface SecurityConfig {
    maxAttempts?: number;
    onViolation?: (type: string, details?: any) => void;
    onBlocked?: () => void;
}

type SecurityTypes = 'right_click' | 'copy_paste' | 'dev_tools' | 'tab_switch' | 'fullscreen_exit' | 'idle_timeout' | 'suspicious_activity';

interface SecurityEvent {
    type: SecurityTypes;
    timestamp: Date;
    details?: any;
}

interface UseExamSecurityReturn {
    isFullscreen: boolean;
    securityViolations: SecurityEvent[];
    violations: SecurityEvent[];
    isIdle: boolean;
    isBlocked: boolean;
    attemptCount: number;
    enterFullscreen: () => Promise<void>;
    exitFullscreen: () => Promise<void>;
    clearViolations: () => void;
    resetViolations: () => void;
    getSecurityScore: () => number;
    securityEnabled: boolean;
}

/**
 * React hook to manage and enforce exam security features such as fullscreen enforcement,
 * tab switch detection, copy/paste prevention, context menu disabling, and print prevention.
 * 
 * This hook tracks security violations, blocks the user after a configurable number of violations,
 * and provides utility functions to interact with security mechanisms (e.g., entering/exiting fullscreen).
 * 
 * @param config - Optional configuration object for customizing security behavior.
 * @param config.maxAttempts - Maximum allowed security violations before blocking the user (default: 3).
 * @param config.onViolation - Callback invoked when a security violation occurs.
 * @param config.onBlocked - Callback invoked when the user is blocked due to too many violations.
 * 
 * @returns An object containing:
 * - `isFullscreen`: Whether the exam is currently in fullscreen mode.
 * - `securityViolations`: Array of recorded security violation events.
 * - `violations`: Alias for `securityViolations`.
 * - `isIdle`: Whether the user is considered idle (currently always `false`).
 * - `isBlocked`: Whether the user is blocked due to exceeding violation attempts.
 * - `attemptCount`: Number of security violations detected.
 * - `enterFullscreen`: Function to programmatically enter fullscreen mode.
 * - `exitFullscreen`: Function to programmatically exit fullscreen mode.
 * - `clearViolations`: Function to clear all recorded violations and reset state.
 * - `resetViolations`: Alias for `clearViolations`.
 * - `getSecurityScore`: Function returning a score (0-100) based on the number of violations.
 * - `securityEnabled`: Whether security features are enabled based on exam configuration.
 * 
 * @example
 * const {
 *   isFullscreen,
 *   securityViolations,
 *   isBlocked,
 *   enterFullscreen,
 *   exitFullscreen,
 *   clearViolations,
 *   getSecurityScore,
 *   securityEnabled,
 * } = useExamSecurity({ maxAttempts: 2, onViolation: handleViolation });
 */
export function useExamSecurity(config: SecurityConfig = {}): UseExamSecurityReturn {
    const examConfig = useExamConfig();

    const securityEnabled = isSecurityEnabled(examConfig);

    const {
        maxAttempts = 3,
        onViolation,
        onBlocked,
    } = config; const [isFullscreen, setIsFullscreen] = useState(Boolean(document.fullscreenElement));

    const [programmaticExit, setProgrammaticExit] = useState(false);

    const [securityViolations, setSecurityViolations] = useState<SecurityEvent[]>([]);

    const [isIdle] = useState(false);

    const [isBlocked, setIsBlocked] = useState(false);

    const [attemptCount, setAttemptCount] = useState(0);

    const addViolation = useCallback((type: SecurityEvent['type'], details?: any) => {
        if (!securityEnabled) return;

        const violation: SecurityEvent = {
            type,
            timestamp: new Date(),
            details,
        };

        setSecurityViolations(prev => [...prev, violation]);

        setAttemptCount(prev => {
            const newCount = prev + 1;

            if (onViolation) {
                onViolation(type, details);
            }

            if (newCount >= maxAttempts) {
                setIsBlocked(true);
                if (onBlocked) {
                    onBlocked();
                }
            }

            return newCount;
        });
    }, [securityEnabled, maxAttempts, onViolation, onBlocked]);

    const enterFullscreen = useCallback(async () => {
        try {
            await document.documentElement.requestFullscreen();
            setIsFullscreen(true);
        } catch (error) {
        }
    }, []);

    const exitFullscreen = useCallback(async () => {
        try {
            setProgrammaticExit(true);

            await document.exitFullscreen();

            setIsFullscreen(false);

            setTimeout(() => setProgrammaticExit(false), 100);
        } catch (error) {
            setProgrammaticExit(false);
        }
    }, []);

    const clearViolations = useCallback(() => {
        setSecurityViolations([]);

        setAttemptCount(0);

        setIsBlocked(false);
    }, []);

    const resetViolations = clearViolations;

    const getSecurityScore = useCallback(() => {
        if (securityViolations.length === 0) return 100;
        return Math.max(0, 100 - (securityViolations.length * 10));
    }, [securityViolations.length]);

    useEffect(() => {
        if (!securityEnabled) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (isFeatureEnabled(examConfig, 'devToolsDetection')) {
                if (e.key === 'F12' ||
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.key === 'u')) {
                    e.preventDefault();
                    return;
                }
            }

            if (isFeatureEnabled(examConfig, 'printPrevention')) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    return;
                }
            }

            if (isFeatureEnabled(examConfig, 'copyPastePrevention')) {
                if ((e.ctrlKey && e.key === 'c') || (e.ctrlKey && e.key === 'x')) {
                    e.preventDefault();
                    return;
                }
                if (e.ctrlKey && e.key === 'v') {
                    e.preventDefault();
                    return;
                }
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    return;
                }
            }
        };

        const handleVisibilityChange = () => {
            if (!isFeatureEnabled(examConfig, 'tabSwitchDetection')) return;

            if (document.hidden) {
                addViolation('tab_switch');
            }
        };

        const handleCopy = (e: ClipboardEvent) => {
            if (!isFeatureEnabled(examConfig, 'copyPastePrevention')) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handlePaste = (e: ClipboardEvent) => {
            if (!isFeatureEnabled(examConfig, 'copyPastePrevention')) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handleCut = (e: ClipboardEvent) => {
            if (!isFeatureEnabled(examConfig, 'copyPastePrevention')) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handleContextMenu = (e: MouseEvent) => {
            if (!isFeatureEnabled(examConfig, 'contextMenuDisabled')) return;

            e.preventDefault();
        };

        const handleFullscreenChange = () => {
            if (!isFeatureEnabled(examConfig, 'fullscreenRequired')) return;

            const isCurrentlyFullscreen = Boolean(document.fullscreenElement);
            setIsFullscreen(isCurrentlyFullscreen);

            if (!isCurrentlyFullscreen && isFullscreen && !programmaticExit) {
                addViolation('fullscreen_exit');
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        document.addEventListener('visibilitychange', handleVisibilityChange);

        document.addEventListener('copy', handleCopy);

        document.addEventListener('paste', handlePaste);

        document.addEventListener('cut', handleCut);

        document.addEventListener('contextmenu', handleContextMenu);

        document.addEventListener('fullscreenchange', handleFullscreenChange);

        let originalPrint: (() => void) | null = null;
        if (isFeatureEnabled(examConfig, 'printPrevention')) {
            originalPrint = window.print;
            window.print = () => {
            };
        }

        return () => {
            document.removeEventListener('keydown', handleKeyDown);

            document.removeEventListener('visibilitychange', handleVisibilityChange);

            document.removeEventListener('copy', handleCopy);

            document.removeEventListener('paste', handlePaste);

            document.removeEventListener('cut', handleCut);

            document.removeEventListener('contextmenu', handleContextMenu);

            document.removeEventListener('fullscreenchange', handleFullscreenChange);

            if (originalPrint) {
                window.print = originalPrint;
            }
        };
    }, [securityEnabled, addViolation, examConfig, isFullscreen, programmaticExit]);

    return {
        isFullscreen,
        securityViolations,
        violations: securityViolations,
        isIdle,
        isBlocked,
        attemptCount,
        enterFullscreen,
        exitFullscreen,
        clearViolations,
        resetViolations,
        getSecurityScore,
        securityEnabled,
    };
}