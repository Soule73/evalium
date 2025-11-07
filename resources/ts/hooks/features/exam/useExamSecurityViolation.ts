import { useState, useCallback, useEffect } from 'react';
import { route } from 'ziggy-js';
import axios from 'axios';
import {
    VIOLATION_TYPES,
    applyConfigurableSecurityMeasures,
    attachConfigurableEventListeners,
    detachConfigurableEventListeners,
    removeSecurityMeasures
} from '@/utils';
import { useExamConfig, isSecurityEnabled, isFeatureEnabled } from './useExamConfig';
import { securityViolationLabel } from '@/utils';

interface UseExamSecurityViolationOptions {
    examId: number;
    onViolation?: (type: string) => void;
}

/**
 * Hook de gestion des violations de sécurité pendant un examen
 * 
 * Ce hook implémente un système simplifié de sécurité pour les examens en ligne,
 * en utilisant des utilitaires modulaires pour la réutilisabilité et la maintenabilité.
 * 
 * @param examId - L'identifiant unique de l'examen
 * @param onViolation - Callback optionnel appelé lors d'une violation
 * 
 * @returns Objet contenant les états et gestionnaires de sécurité
 * 
 * @example
 * ```typescript
 * const {
 *   examTerminated,
 *   terminationReason,
 *   handleViolation,
 *   handleBlocked
 * } = useExamSecurityViolation({
 *   examId: 123,
 *   onViolation: (type) => console.log(`Violation: ${type}`)
 * });
 * 
 * if (examTerminated) {
 *   return <SecurityViolationPage reason={terminationReason} />;
 * }
 * ```
 */
export function useExamSecurityViolation({ examId, onViolation }: UseExamSecurityViolationOptions) {
    const [examTerminated, setExamTerminated] = useState<boolean>(false);
    const [terminationReason, setTerminationReason] = useState<string>('');

    const examConfig = useExamConfig();
    const securityEnabled = isSecurityEnabled(examConfig);

    /**
     * Applique les mesures de sécurité en fonction de la configuration
     * Nettoie automatiquement lors du démontage
     */
    useEffect(() => {
        if (!securityEnabled) {
            return;
        }

        // Configuration des fonctionnalités de sécurité
        const securityConfig = {
            devToolsDetection: isFeatureEnabled(examConfig, 'devToolsDetection'),
            copyPastePrevention: isFeatureEnabled(examConfig, 'copyPastePrevention'),
            contextMenuDisabled: isFeatureEnabled(examConfig, 'contextMenuDisabled'),
            printPrevention: isFeatureEnabled(examConfig, 'printPrevention'),
            tabSwitchDetection: isFeatureEnabled(examConfig, 'tabSwitchDetection')
        };

        // Applique les mesures de sécurité selon la configuration
        const handlers = applyConfigurableSecurityMeasures(securityConfig);

        // Attache les événements selon la configuration
        attachConfigurableEventListeners(handlers, securityConfig);

        // Nettoyage
        return () => {
            detachConfigurableEventListeners(handlers, securityConfig);
            removeSecurityMeasures();
        };
    }, [securityEnabled, examConfig]);

    /**
     * Termine immédiatement l'examen suite à une violation de sécurité
     * 
     * Responsabilités :
     * - Définit la raison de terminaison selon le type de violation
     * - Met à jour l'état local (examTerminated = true)
     * - Envoie les données de violation au serveur
     * - Sauvegarde les réponses actuelles de l'étudiant
     * 
     * @param violationType - Type de violation détectée
     * @param answers - Réponses actuelles à sauvegarder
     */
    const terminateExamForViolation = useCallback(async (
        violationType: string,
        answers: Record<number, string | number | number[]>
    ) => {
        const reason = securityViolationLabel(violationType);

        setTerminationReason(reason);
        setExamTerminated(true);

        try {
            await axios.post(route('student.exams.security-violation', examId), {
                violation_type: violationType,
                violation_details: reason,
                answers: answers
            });
        } catch (error) {
            setExamTerminated(true);
        }

        if (onViolation) {
            onViolation(violationType);
        }
    }, [examId, onViolation]);

    /**
     * Gestionnaire pour les violations critiques de sécurité
     * 
     * Traite uniquement les violations qui entraînent une terminaison immédiate :
     * - Changement d'onglet (tab_switch)
     * - Sortie du mode plein écran (fullscreen_exit)
     * 
     * @param type - Type de violation détectée
     * @param answers - Réponses actuelles à sauvegarder
     */
    const handleViolation = useCallback((
        type: string,
        answers: Record<number, string | number | number[]>
    ) => {
        if (type === VIOLATION_TYPES.TAB_SWITCH || type === VIOLATION_TYPES.FULLSCREEN_EXIT) {
            terminateExamForViolation(type, answers);
        }
    }, [terminateExamForViolation]);

    return {
        examTerminated,
        terminationReason,
        handleViolation,
        terminateExamForViolation
    };
}