import { useCallback, useEffect, useLayoutEffect, useRef } from 'react';
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

    useEffect(() => {
        onTimeEndRef.current = onTimeEnd;
    }, [onTimeEnd]);

    useLayoutEffect(() => {
        if (remainingSeconds !== null && remainingSeconds >= 0) {
            setTimeLeft(remainingSeconds);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

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

        if (!timerRef.current && timeLeft > 0) {
            timerRef.current = setInterval(tick, 1000);
        }

        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [isSubmitting, isActive, timeLeft, tick]);

    return { timeLeft };
};
