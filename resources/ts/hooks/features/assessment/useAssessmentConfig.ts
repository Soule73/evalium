import { usePage } from '@inertiajs/react';

interface AssessmentConfig {
    devMode: boolean;
    securityEnabled: boolean;
    features: {
        fullscreenRequired: boolean;
        tabSwitchDetection: boolean;
        devToolsDetection: boolean;
        copyPastePrevention: boolean;
        contextMenuDisabled: boolean;
        printPrevention: boolean;
    };
    timing: {
        minAssessmentDurationMinutes: number;
        autoSubmitOnTimeEnd: boolean;
    };
}

interface PageProps extends Record<string, any> {
    assessmentConfig?: AssessmentConfig;
}

export function useAssessmentConfig(): AssessmentConfig {
    const { props } = usePage<PageProps>();

    // Configuration par défaut si non fournie par le backend
    const defaultConfig: AssessmentConfig = {
        devMode: false,
        securityEnabled: true,
        features: {
            fullscreenRequired: true,
            tabSwitchDetection: true,
            devToolsDetection: true,
            copyPastePrevention: true,
            contextMenuDisabled: true,
            printPrevention: true,
        },
        timing: {
            minAssessmentDurationMinutes: 2,
            autoSubmitOnTimeEnd: true,
        },
    };

    return props.assessmentConfig || defaultConfig;
}

export function useSecurityEnabled(): boolean {
    const config = useAssessmentConfig();
    return config.securityEnabled && !config.devMode;
}

export function useFeatureEnabled(feature: keyof AssessmentConfig['features']): boolean {
    const config = useAssessmentConfig();
    // Si en mode dev, toutes les fonctionnalités sont désactivées
    if (config.devMode) {
        return false;
    }

    // Si la sécurité globale est désactivée
    if (!config.securityEnabled) {
        return false;
    }

    return config.features[feature];
}

// Fonctions utilitaires pour les cas où on a déjà la config
export function isSecurityEnabled(config: AssessmentConfig): boolean {
    return config.securityEnabled && !config.devMode;
}

export function isFeatureEnabled(config: AssessmentConfig, feature: keyof AssessmentConfig['features']): boolean {
    // Si en mode dev, toutes les fonctionnalités sont désactivées
    if (config.devMode) {
        return false;
    }

    // Si la sécurité globale est désactivée
    if (!config.securityEnabled) {
        return false;
    }

    return config.features[feature];
}