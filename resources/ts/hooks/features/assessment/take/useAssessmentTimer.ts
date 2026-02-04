import { useEffect, useRef, useCallback } from 'react';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface UseAssessmentTimerParams {
  duration: number;
  onTimeEnd: () => void;
  isSubmitting: boolean;
}

/**
 * Custom hook to manage the assessment timer countdown.
 * Automatically submits the assessment when time runs out.
 */
export const useAssessmentTimer = ({ duration, onTimeEnd, isSubmitting }: UseAssessmentTimerParams) => {
  const { timeLeft, setTimeLeft } = useAssessmentTakeStore(useShallow((state) => ({
    timeLeft: state.timeLeft,
    setTimeLeft: state.setTimeLeft,
  })));

  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const onTimeEndRef = useRef(onTimeEnd);

  useEffect(() => {
    onTimeEndRef.current = onTimeEnd;
  }, [onTimeEnd]);

  useEffect(() => {
    setTimeLeft(duration * 60);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [duration]);

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
    if (isSubmitting) {
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
  }, [isSubmitting, timeLeft, tick]);

  return { timeLeft };
};
