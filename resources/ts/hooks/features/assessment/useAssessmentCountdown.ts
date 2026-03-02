import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { type CountdownTime, computeCountdown } from '@evalium/utils/assessment/take/timeUtils';

interface UseAssessmentCountdownResult {
    countdown: CountdownTime | null;
    isStarting: boolean;
}

/**
 * Computes a live countdown to the given ISO date string.
 *
 * When the target time is reached, triggers an Inertia partial reload
 * restricted to the `availability` prop so the page refreshes without
 * a full navigation, making the start button appear automatically.
 *
 * @param scheduledAt - ISO date string of the scheduled start time.
 * @param enabled     - Whether the countdown should be active.
 */
export const useAssessmentCountdown = (
    scheduledAt: string | null,
    enabled: boolean,
): UseAssessmentCountdownResult => {
    const [countdown, setCountdown] = useState<CountdownTime | null>(null);
    const [isStarting, setIsStarting] = useState(false);
    const hasReloaded = useRef(false);

    useEffect(() => {
        if (!enabled || !scheduledAt) {
            return;
        }

        const target = new Date(scheduledAt).getTime();

        const tick = () => {
            const diff = target - Date.now();

            if (diff <= 0) {
                setCountdown({ days: 0, hours: 0, minutes: 0, seconds: 0 });

                if (!hasReloaded.current) {
                    hasReloaded.current = true;
                    setIsStarting(true);
                    router.reload({ only: ['availability'] });
                }

                return;
            }

            setCountdown(computeCountdown(diff));
        };

        tick();

        const interval = setInterval(tick, 1_000);

        return () => clearInterval(interval);
    }, [scheduledAt, enabled]);

    return { countdown, isStarting };
};
