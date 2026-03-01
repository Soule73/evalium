import { useCallback } from 'react';
import { route } from 'ziggy-js';
import axios from 'axios';
import { ASSESSMENT_VIOLATION_TYPES } from '@/utils/assessment/take';
import { getViolationTranslationKey } from '@/utils/assessment/take';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface UseAssessmentSecurityViolationOptions {
    assessmentId: number;
    onViolation?: (type: string) => void;
}

export function useAssessmentSecurityViolation({
    assessmentId,
    onViolation,
}: UseAssessmentSecurityViolationOptions) {
    const { assessmentTerminated, terminationReason, setAssessmentTerminated } =
        useAssessmentTakeStore(
            useShallow((state) => ({
                assessmentTerminated: state.assessmentTerminated,
                terminationReason: state.terminationReason,
                setAssessmentTerminated: state.setAssessmentTerminated,
            })),
        );

    const { t } = useTranslations();

    const getViolationLabel = useCallback(
        (violationType: string): string => {
            const translationKey = getViolationTranslationKey(violationType);
            return t(translationKey);
        },
        [t],
    );

    const terminateAssessmentForViolation = useCallback(
        async (violationType: string, answers: Record<number, string | number | number[]>) => {
            const reason = getViolationLabel(violationType);

            setAssessmentTerminated(true, reason);

            try {
                await axios.post(route('student.assessments.security-violation', assessmentId), {
                    violation_type: violationType,
                    violation_details: reason,
                    answers: answers,
                });
            } catch {
                setAssessmentTerminated(true, reason);
            }

            if (onViolation) {
                onViolation(violationType);
            }
        },
        [assessmentId, onViolation, getViolationLabel, setAssessmentTerminated],
    );

    const handleViolation = useCallback(
        (type: string, answers: Record<number, string | number | number[]>) => {
            if (
                type === ASSESSMENT_VIOLATION_TYPES.TAB_SWITCH ||
                type === ASSESSMENT_VIOLATION_TYPES.FULLSCREEN_EXIT
            ) {
                terminateAssessmentForViolation(type, answers);
            }
        },
        [terminateAssessmentForViolation],
    );

    return {
        assessmentTerminated,
        terminationReason,
        handleViolation,
        terminateAssessmentForViolation,
    };
}
