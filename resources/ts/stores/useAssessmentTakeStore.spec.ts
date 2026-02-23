import { describe, it, expect, beforeEach } from 'vitest';
import { useAssessmentTakeStore } from './useAssessmentTakeStore';

describe('useAssessmentTakeStore', () => {
    beforeEach(() => {
        useAssessmentTakeStore.getState().reset();
    });

    it('initialises wizardStep to answering', () => {
        const state = useAssessmentTakeStore.getState();
        expect(state.wizardStep).toBe('answering');
    });

    it('setWizardStep transitions to reviewing', () => {
        useAssessmentTakeStore.getState().setWizardStep('reviewing');
        expect(useAssessmentTakeStore.getState().wizardStep).toBe('reviewing');
    });

    it('setWizardStep transitions to submitting', () => {
        useAssessmentTakeStore.getState().setWizardStep('submitting');
        expect(useAssessmentTakeStore.getState().wizardStep).toBe('submitting');
    });

    it('reset restores wizardStep to answering', () => {
        useAssessmentTakeStore.getState().setWizardStep('reviewing');
        useAssessmentTakeStore.getState().reset();
        expect(useAssessmentTakeStore.getState().wizardStep).toBe('answering');
    });
});
