/* eslint-disable react-refresh/only-export-components */
import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';
import type { ClassModel, User } from '@/types';
import type { CreatedUserCredentials } from '@/types';

export type WizardStep = 1 | 2 | 3 | 'result';

export interface BulkEnrolledStudent {
    student_id: number;
    student_name: string;
    student_email: string;
    enrollment_id: number;
    status: string;
    password?: string;
}

export interface BulkEnrollmentFailure {
    student_id: number;
    student_name: string;
    reason: string;
}

export interface BulkEnrollmentResult {
    class_name: string;
    enrolled: BulkEnrolledStudent[];
    failed: BulkEnrollmentFailure[];
}

interface EnrollmentWizardState {
    step: WizardStep;
    selectedClass: ClassModel | null;
    selectedStudents: User[];
    newlyCreatedStudents: CreatedUserCredentials[];
    sendCredentials: boolean;
    bulkResult: BulkEnrollmentResult | null;
}

interface EnrollmentWizardActions {
    goToStep: (step: WizardStep) => void;
    setSelectedClass: (classModel: ClassModel | null) => void;
    setSelectedStudents: (students: User[]) => void;
    addNewlyCreatedStudent: (credentials: CreatedUserCredentials, student: User) => void;
    setSendCredentials: (value: boolean) => void;
    setBulkResult: (result: BulkEnrollmentResult) => void;
    reset: () => void;
}

interface EnrollmentWizardContextValue {
    state: EnrollmentWizardState;
    actions: EnrollmentWizardActions;
}

const initialState: EnrollmentWizardState = {
    step: 1,
    selectedClass: null,
    selectedStudents: [],
    newlyCreatedStudents: [],
    sendCredentials: false,
    bulkResult: null,
};

const EnrollmentWizardContext = createContext<EnrollmentWizardContextValue | null>(null);

interface EnrollmentWizardProviderProps {
    children: ReactNode;
}

/**
 * Provides state and actions for the multi-step enrollment wizard.
 */
export function EnrollmentWizardProvider({ children }: EnrollmentWizardProviderProps) {
    const [state, setState] = useState<EnrollmentWizardState>(initialState);

    const goToStep = useCallback((step: WizardStep) => {
        setState((prev) => ({ ...prev, step }));
    }, []);

    const setSelectedClass = useCallback((classModel: ClassModel | null) => {
        setState((prev) => ({ ...prev, selectedClass: classModel }));
    }, []);

    const setSelectedStudents = useCallback((students: User[]) => {
        setState((prev) => ({ ...prev, selectedStudents: students }));
    }, []);

    const addNewlyCreatedStudent = useCallback(
        (credentials: CreatedUserCredentials, student: User) => {
            setState((prev) => ({
                ...prev,
                newlyCreatedStudents: [...prev.newlyCreatedStudents, credentials],
                selectedStudents: [...prev.selectedStudents, student],
            }));
        },
        [],
    );

    const setSendCredentials = useCallback((value: boolean) => {
        setState((prev) => ({ ...prev, sendCredentials: value }));
    }, []);

    const setBulkResult = useCallback((result: BulkEnrollmentResult) => {
        setState((prev) => ({ ...prev, bulkResult: result, step: 'result' }));
    }, []);

    const reset = useCallback(() => {
        setState(initialState);
    }, []);

    const actions: EnrollmentWizardActions = useMemo(
        () => ({
            goToStep,
            setSelectedClass,
            setSelectedStudents,
            addNewlyCreatedStudent,
            setSendCredentials,
            setBulkResult,
            reset,
        }),
        [
            goToStep,
            setSelectedClass,
            setSelectedStudents,
            addNewlyCreatedStudent,
            setSendCredentials,
            setBulkResult,
            reset,
        ],
    );

    const value = useMemo(() => ({ state, actions }), [state, actions]);

    return (
        <EnrollmentWizardContext.Provider value={value}>
            {children}
        </EnrollmentWizardContext.Provider>
    );
}

/**
 * Returns the enrollment wizard context value.
 * Must be used within EnrollmentWizardProvider.
 */
export function useEnrollmentWizard(): EnrollmentWizardContextValue {
    const context = useContext(EnrollmentWizardContext);
    if (!context) {
        throw new Error('useEnrollmentWizard must be used within EnrollmentWizardProvider');
    }
    return context;
}
