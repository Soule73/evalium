import { usePage } from '@inertiajs/react';

interface ExamConfig {
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
        minExamDurationMinutes: number;
        autoSubmitOnTimeEnd: boolean;
    };
}

interface PageProps extends Record<string, any> {
    examConfig?: ExamConfig;
}

export function useExamConfig(): ExamConfig {
    const { props } = usePage<PageProps>();

    // Configuration par défaut si non fournie par le backend
    const defaultConfig: ExamConfig = {
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
            minExamDurationMinutes: 2,
            autoSubmitOnTimeEnd: true,
        },
    };

    return props.examConfig || defaultConfig;
}

export function useSecurityEnabled(): boolean {
    const config = useExamConfig();
    return config.securityEnabled && !config.devMode;
}

export function useFeatureEnabled(feature: keyof ExamConfig['features']): boolean {
    const config = useExamConfig();

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
export function isSecurityEnabled(config: ExamConfig): boolean {
    return config.securityEnabled && !config.devMode;
}

export function isFeatureEnabled(config: ExamConfig, feature: keyof ExamConfig['features']): boolean {
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