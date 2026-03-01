import { useCallback, useEffect, useRef } from 'react';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface UseAssessmentTimerParams {
    remainingSeconds: number | null;
    onTimeEnd: () => void;
    isSubmitting: boolean;
    isActive?: boolean;
}

/**
 * Custom hook to manage the assessment timer countdown.
 *
 * Uses server-provided remaining seconds as source of truth
 * instead of computing from duration_minutes on the client.
 * Returns early for homework mode (remainingSeconds is null).
 */
export const useAssessmentTimer = ({
    remainingSeconds,
    onTimeEnd,
    isSubmitting,
    isActive = true,
}: UseAssessmentTimerParams) => {
    const { timeLeft, setTimeLeft } = useAssessmentTakeStore(
        useShallow((state) => ({
            timeLeft: state.timeLeft,
            setTimeLeft: state.setTimeLeft,
        })),
    );

    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const onTimeEndRef = useRef(onTimeEnd);
    const initializedRef = useRef(false);

    useEffect(() => {
        onTimeEndRef.current = onTimeEnd;
    }, [onTimeEnd]);

    useEffect(() => {
        if (!initializedRef.current && remainingSeconds !== null && remainingSeconds >= 0) {
            setTimeLeft(remainingSeconds);
            initializedRef.current = true;
        }
    }, [remainingSeconds, setTimeLeft]);

    const tick = useCallback(() => {
        setTimeLeft((prev) => {
            if (prev <= 1) {
                if (timerRef.current) {
                    clearInterval(timerRef.current);
                    timerRef.current = null;
                }
                onTimeEndRef.current();
                return 0;
            }
            return prev - 1;
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (isSubmitting || !isActive) {
            if (timerRef.current) {
                clearInterval(timerRef.current);
                timerRef.current = null;
            }
            return;
        }

        const effectiveTimeLeft =
            timeLeft > 0
                ? timeLeft
                : remainingSeconds !== null && remainingSeconds > 0
                  ? remainingSeconds
                  : 0;

        if (effectiveTimeLeft > 0 && timeLeft === 0) {
            setTimeLeft(effectiveTimeLeft);
        }

        if (!timerRef.current && effectiveTimeLeft > 0) {
            timerRef.current = setInterval(tick, 1000);
        }

        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [isSubmitting, isActive, timeLeft, remainingSeconds, tick, setTimeLeft]);

    return { timeLeft };
};
