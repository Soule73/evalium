import { useState, useEffect, useCallback, useMemo } from 'react';
import { useAssessmentConfig, isFeatureEnabled } from '../useAssessmentConfig';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
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
export function useAssessmentFullscreen({ security }: UseExamFullscreenOptions) {
    const { showFullscreenModal, setShowFullscreenModal, examCanStart, setExamCanStart } = useAssessmentTakeStore(
        useShallow((state) => ({
            showFullscreenModal: state.showFullscreenModal,
            setShowFullscreenModal: state.setShowFullscreenModal,
            examCanStart: state.assessmentCanStart,
            setExamCanStart: state.setAssessmentCanStart,
        }))
    );

    const [fullscreenRequired, setFullscreenRequired] = useState<boolean>(false);

    const assessmentConfig = useAssessmentConfig();

    const fullscreenIsSupported = useMemo(() => isFullscreenSupported(), []);

    useEffect(() => {
        const shouldRequireFullscreen = isFeatureEnabled(assessmentConfig, 'fullscreenRequired')
            && assessmentConfig.securityEnabled
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [assessmentConfig, fullscreenIsSupported]);

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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [security]);

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