import { useState, useEffect, useCallback, useMemo } from 'react';
import { useExamConfig, isFeatureEnabled } from '../useExamConfig';
import { useExamTakeStore } from '@/stores/useExamTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { isFullscreenSupported } from '@/utils/exam/take';

interface UseExamFullscreenOptions {
    security: {
        enterFullscreen?: () => Promise<void>;
        exitFullscreen?: () => Promise<void>;
    } | null;
}

/**
 * React hook to manage fullscreen requirements and modal display for an exam environment.
 *
 * This hook determines if fullscreen mode is required based on exam configuration,
 * manages the display of a modal prompting the user to enter fullscreen, and provides
 * methods to programmatically enter and exit fullscreen mode.
 *
 * @param options - Options for configuring fullscreen security behavior.
 * @param options.security - An object containing methods to enter and exit fullscreen mode.
 *
 * @returns An object containing:
 * - `showFullscreenModal`: Whether the fullscreen modal should be displayed.
 * - `fullscreenRequired`: Whether fullscreen is required for the exam.
 * - `examCanStart`: Whether the exam can be started.
 * - `enterFullscreen`: Function to trigger entering fullscreen mode.
 * - `exitFullscreen`: Function to trigger exiting fullscreen mode.
 */
export function useExamFullscreen({ security }: UseExamFullscreenOptions) {
    const { showFullscreenModal, setShowFullscreenModal, examCanStart, setExamCanStart } = useExamTakeStore(
        useShallow((state) => ({
            showFullscreenModal: state.showFullscreenModal,
            setShowFullscreenModal: state.setShowFullscreenModal,
            examCanStart: state.examCanStart,
            setExamCanStart: state.setExamCanStart,
        }))
    );

    const [fullscreenRequired, setFullscreenRequired] = useState<boolean>(false);

    const examConfig = useExamConfig();

    const fullscreenIsSupported = useMemo(() => isFullscreenSupported(), []);

    useEffect(() => {
        const shouldRequireFullscreen = isFeatureEnabled(examConfig, 'fullscreenRequired')
            && examConfig.securityEnabled
            && fullscreenIsSupported;

        if (shouldRequireFullscreen) {
            setFullscreenRequired(true);
            setShowFullscreenModal(true);
            setExamCanStart(false);
        } else {
            setFullscreenRequired(false);
            setShowFullscreenModal(false);
            setExamCanStart(true);
        }
    }, [examConfig, fullscreenIsSupported, setShowFullscreenModal, setExamCanStart]);

    const enterFullscreen = useCallback(async () => {
        if (security?.enterFullscreen) {
            try {
                await security.enterFullscreen();
                setShowFullscreenModal(false);
                setExamCanStart(true);
            } catch (error) {
                setShowFullscreenModal(false);
                setExamCanStart(true);
            }
        } else {
            setShowFullscreenModal(false);
            setExamCanStart(true);
        }
    }, [security, setShowFullscreenModal, setExamCanStart]);

    const exitFullscreen = useCallback(async () => {
        if (security?.exitFullscreen) {
            try {
                await security.exitFullscreen();
            } catch (error) {
            }
        }
    }, [security]);

    return {
        showFullscreenModal,
        fullscreenRequired,
        examCanStart,
        enterFullscreen,
        exitFullscreen
    };
}