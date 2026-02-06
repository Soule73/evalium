import { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { useAssessmentConfig, isSecurityEnabled, isFeatureEnabled } from '../useAssessmentConfig';
import { isInFullscreen, AssessmentViolationType } from '@/utils/assessment/take';

interface SecurityConfig {
    maxAttempts?: number;
    onViolation?: (type: string, details?: any) => void;
    onBlocked?: () => void;
}

interface SecurityEvent {
    type: AssessmentViolationType | string;
    timestamp: Date;
    details?: any;
}

interface UseAssessmentSecurityReturn {
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

export function useAssessmentSecurity(config: SecurityConfig = {}): UseAssessmentSecurityReturn {
    const assessmentConfig = useAssessmentConfig();

    const securityEnabled = isSecurityEnabled(assessmentConfig);

    const {
        maxAttempts = 3,
        onViolation,
        onBlocked,
    } = config;

    const onViolationRef = useRef(onViolation);
    const onBlockedRef = useRef(onBlocked);

    useEffect(() => {
        onViolationRef.current = onViolation;
        onBlockedRef.current = onBlocked;
    }, [onViolation, onBlocked]);

    const securityFeatures = useMemo(() => ({
        devToolsDetection: isFeatureEnabled(assessmentConfig, 'devToolsDetection'),
        printPrevention: isFeatureEnabled(assessmentConfig, 'printPrevention'),
        copyPastePrevention: isFeatureEnabled(assessmentConfig, 'copyPastePrevention'),
        tabSwitchDetection: isFeatureEnabled(assessmentConfig, 'tabSwitchDetection'),
        contextMenuDisabled: isFeatureEnabled(assessmentConfig, 'contextMenuDisabled'),
        fullscreenRequired: isFeatureEnabled(assessmentConfig, 'fullscreenRequired'),
    }), [assessmentConfig]);

    const [isFullscreen, setIsFullscreen] = useState(isInFullscreen());

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

            if (onViolationRef.current) {
                onViolationRef.current(type, details);
            }

            if (newCount >= maxAttempts) {
                setIsBlocked(true);
                if (onBlockedRef.current) {
                    onBlockedRef.current();
                }
            }

            return newCount;
        });
    }, [securityEnabled, maxAttempts]);

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
            if (securityFeatures.devToolsDetection) {
                if (e.key === 'F12' ||
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.key === 'u')) {
                    e.preventDefault();
                    return;
                }
            }

            if (securityFeatures.printPrevention) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    return;
                }
            }

            if (securityFeatures.copyPastePrevention) {
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
            if (!securityFeatures.tabSwitchDetection) return;

            if (document.hidden) {
                addViolation('tab_switch');
            }
        };

        const handleCopy = (e: ClipboardEvent) => {
            if (!securityFeatures.copyPastePrevention) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handlePaste = (e: ClipboardEvent) => {
            if (!securityFeatures.copyPastePrevention) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handleCut = (e: ClipboardEvent) => {
            if (!securityFeatures.copyPastePrevention) return;

            e.preventDefault();
            e.stopPropagation();
        };

        const handleContextMenu = (e: MouseEvent) => {
            if (!securityFeatures.contextMenuDisabled) return;

            e.preventDefault();
        };

        const handleFullscreenChange = () => {
            if (!securityFeatures.fullscreenRequired) return;

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
        if (securityFeatures.printPrevention) {
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
    }, [securityEnabled, addViolation, securityFeatures, isFullscreen, programmaticExit]);

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