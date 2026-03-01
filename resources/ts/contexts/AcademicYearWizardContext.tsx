/* eslint-disable react-refresh/only-export-components */
import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';
import type { AcademicYear, AcademicYearFormData } from '@/types';

export type AcademicYearWizardStep = 1 | 2 | 3 | 'result';

export interface AcademicYearWizardResult {
    year: AcademicYear;
    duplicated_classes_count: number;
}

interface AcademicYearWizardState {
    step: AcademicYearWizardStep;
    formData: AcademicYearFormData;
    selectedClassIds: number[];
    result: AcademicYearWizardResult | null;
}

interface AcademicYearWizardActions {
    goToStep: (step: AcademicYearWizardStep) => void;
    setFormData: (data: AcademicYearFormData) => void;
    setSelectedClassIds: (ids: number[]) => void;
    setResult: (result: AcademicYearWizardResult) => void;
    reset: () => void;
}

interface AcademicYearWizardContextValue {
    state: AcademicYearWizardState;
    actions: AcademicYearWizardActions;
}

const initialFormData: AcademicYearFormData = {
    name: '',
    start_date: '',
    end_date: '',
    is_current: false,
    semesters: [],
};

const initialState: AcademicYearWizardState = {
    step: 1,
    formData: initialFormData,
    selectedClassIds: [],
    result: null,
};

const AcademicYearWizardContext = createContext<AcademicYearWizardContextValue | null>(null);

interface AcademicYearWizardProviderProps {
    children: ReactNode;
    initialFormData?: AcademicYearFormData;
    initialClassIds?: number[];
}

/**
 * Provides shared state and actions for the multi-step academic year creation wizard.
 */
export function AcademicYearWizardProvider({
    children,
    initialFormData: formDataOverride,
    initialClassIds = [],
}: AcademicYearWizardProviderProps) {
    const [state, setState] = useState<AcademicYearWizardState>({
        ...initialState,
        formData: formDataOverride ?? initialFormData,
        selectedClassIds: initialClassIds,
    });

    const goToStep = useCallback((step: AcademicYearWizardStep) => {
        setState((prev) => ({ ...prev, step }));
    }, []);

    const setFormData = useCallback((data: AcademicYearFormData) => {
        setState((prev) => ({ ...prev, formData: data }));
    }, []);

    const setSelectedClassIds = useCallback((ids: number[]) => {
        setState((prev) => ({ ...prev, selectedClassIds: ids }));
    }, []);

    const setResult = useCallback((result: AcademicYearWizardResult) => {
        setState((prev) => ({ ...prev, result, step: 'result' }));
    }, []);

    const reset = useCallback(() => {
        setState({
            ...initialState,
            formData: formDataOverride ?? initialFormData,
            selectedClassIds: initialClassIds,
        });
    }, [formDataOverride, initialClassIds]);

    const actions = useMemo(
        () => ({ goToStep, setFormData, setSelectedClassIds, setResult, reset }),
        [goToStep, setFormData, setSelectedClassIds, setResult, reset],
    );

    const value = useMemo(() => ({ state, actions }), [state, actions]);

    return (
        <AcademicYearWizardContext.Provider value={value}>
            {children}
        </AcademicYearWizardContext.Provider>
    );
}

/**
 * Returns the current academic year wizard context.
 * Must be used inside an AcademicYearWizardProvider.
 */
export function useAcademicYearWizard(): AcademicYearWizardContextValue {
    const context = useContext(AcademicYearWizardContext);
    if (!context) {
        throw new Error('useAcademicYearWizard must be used within an AcademicYearWizardProvider');
    }
    return context;
}
