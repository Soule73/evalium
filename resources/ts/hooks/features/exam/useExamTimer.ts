import { useEffect, useRef, useState } from 'react';

interface UseExamTimerOptions {
    duration: number;
    onTimeEnd: () => void;
    isSubmitting: boolean;
}

export function useExamTimer({ duration, onTimeEnd, isSubmitting }: UseExamTimerOptions) {
    const [timeLeft, setTimeLeft] = useState<number>(0);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);
    const onTimeEndRef = useRef(onTimeEnd);
    const isSubmittingRef = useRef(isSubmitting);


    useEffect(() => {
        onTimeEndRef.current = onTimeEnd;
    }, [onTimeEnd]);

    useEffect(() => {
        isSubmittingRef.current = isSubmitting;
    }, [isSubmitting]);

    useEffect(() => {
        const examDurationInSeconds = duration * 60;
        setTimeLeft(examDurationInSeconds);

        intervalRef.current = setInterval(() => {
            setTimeLeft(prev => {
                if (prev <= 1) {
                    if (!isSubmittingRef.current) {
                        onTimeEndRef.current();
                    }
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [duration]);

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