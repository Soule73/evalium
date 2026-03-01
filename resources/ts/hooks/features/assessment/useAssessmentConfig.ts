import { usePage } from '@inertiajs/react';
import { type AssessmentConfig } from '@/utils/assessment/security';

interface PageProps extends Record<string, unknown> {
    assessmentConfig?: AssessmentConfig;
}

export function useAssessmentConfig(): AssessmentConfig {
    const { props } = usePage<PageProps>();

    return props.assessmentConfig ?? { devMode: false };
}

export function useSecurityEnabled(): boolean {
    const config = useAssessmentConfig();
    return !config.devMode;
}

export function useFeatureEnabled(_feature: string): boolean {
    const config = useAssessmentConfig();
    return !config.devMode;
}
