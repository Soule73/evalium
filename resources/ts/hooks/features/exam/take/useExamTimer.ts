import { useEffect, useRef, useCallback } from 'react';
import { useExamTakeStore } from '@/stores/useExamTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { minutesToSeconds } from '@/utils/exam/take';

interface UseExamTimerOptions {
    duration: number;
    onTimeEnd: () => void;
    isSubmitting: boolean;
}

export function useExamTimer({ duration, onTimeEnd, isSubmitting }: UseExamTimerOptions) {
    const { timeLeft, setTimeLeft } = useExamTakeStore(
        useShallow((state) => ({
            timeLeft: state.timeLeft,
            setTimeLeft: state.setTimeLeft,
        }))
    );
    const intervalRef = useRef<NodeJS.Timeout | null>(null);
    const onTimeEndRef = useRef(onTimeEnd);
    const isSubmittingRef = useRef(isSubmitting);

    const decrementTime = useCallback(() => {
        setTimeLeft((prev) => {
            if (prev <= 1) {
                if (!isSubmittingRef.current) {
                    onTimeEndRef.current();
                }
                return 0;
            }
            return prev - 1;
        });
    }, [setTimeLeft]);


    useEffect(() => {
        onTimeEndRef.current = onTimeEnd;
    }, [onTimeEnd]);

    useEffect(() => {
        isSubmittingRef.current = isSubmitting;
    }, [isSubmitting]);

    useEffect(() => {
        const examDurationInSeconds = minutesToSeconds(duration);
        setTimeLeft(examDurationInSeconds);

        intervalRef.current = setInterval(decrementTime, 1000);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [duration, setTimeLeft, decrementTime]);

    useEffect(() => {
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, []);



    return {
        timeLeft
    };
}