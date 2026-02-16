import React, { createContext, useReducer, type ReactNode } from 'react';
import { type ToastType } from './Toast';

export type ToastPosition =
    | 'top-right'
    | 'top-left'
    | 'top-center'
    | 'bottom-right'
    | 'bottom-left'
    | 'bottom-center';

export interface ToastData {
    id: string;
    type: ToastType;
    title?: string;
    message: string;
    autoClose?: boolean;
    duration?: number;
    position?: ToastPosition;
    closeLabel?: string;
}

interface ToastState {
    toasts: ToastData[];
    defaultPosition: ToastPosition;
}

type ToastAction =
    | { type: 'ADD_TOAST'; payload: ToastData }
    | { type: 'REMOVE_TOAST'; payload: string }
    | { type: 'CLEAR_ALL_TOASTS' }
    | { type: 'SET_DEFAULT_POSITION'; payload: ToastPosition };

export interface ToastContextType {
    toasts: ToastData[];
    defaultPosition: ToastPosition;
    addToast: (toast: Omit<ToastData, 'id'>) => string;
    removeToast: (id: string) => void;
    clearAllToasts: () => void;
    setDefaultPosition: (position: ToastPosition) => void;

    success: (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ) => string;
    error: (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ) => string;
    warning: (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ) => string;
    info: (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ) => string;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

const toastReducer = (state: ToastState, action: ToastAction): ToastState => {
    switch (action.type) {
        case 'ADD_TOAST': {
            const newState = {
                ...state,
                toasts: [...state.toasts, action.payload],
            };
            return newState;
        }
        case 'REMOVE_TOAST':
            return {
                ...state,
                toasts: state.toasts.filter((toast) => toast.id !== action.payload),
            };
        case 'CLEAR_ALL_TOASTS':
            return {
                ...state,
                toasts: [],
            };
        case 'SET_DEFAULT_POSITION':
            return {
                ...state,
                defaultPosition: action.payload,
            };
        default:
            return state;
    }
};

const generateId = (): string => {
    return `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
};

interface ToastProviderProps {
    children: ReactNode;
    defaultPosition?: ToastPosition;
}

export const ToastProvider: React.FC<ToastProviderProps> = ({
    children,
    defaultPosition = 'top-right',
}) => {
    const [state, dispatch] = useReducer(toastReducer, {
        toasts: [],
        defaultPosition,
    });

    const addToast = (toast: Omit<ToastData, 'id'>): string => {
        const id = generateId();
        const toastData: ToastData = {
            id,
            position: defaultPosition,
            autoClose: true,
            duration: 5000,
            ...toast,
        };

        dispatch({ type: 'ADD_TOAST', payload: toastData });
        return id;
    };

    const removeToast = (id: string): void => {
        dispatch({ type: 'REMOVE_TOAST', payload: id });
    };

    const clearAllToasts = (): void => {
        dispatch({ type: 'CLEAR_ALL_TOASTS' });
    };

    const setDefaultPosition = (position: ToastPosition): void => {
        dispatch({ type: 'SET_DEFAULT_POSITION', payload: position });
    };

    const success = (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ): string => {
        return addToast({ type: 'success', message, ...options });
    };

    const error = (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ): string => {
        return addToast({
            type: 'error',
            message,
            autoClose: false,
            ...options,
        });
    };

    const warning = (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ): string => {
        return addToast({ type: 'warning', message, ...options });
    };

    const info = (
        message: string,
        options?: Partial<Omit<ToastData, 'id' | 'type' | 'message'>>,
    ): string => {
        return addToast({ type: 'info', message, ...options });
    };

    const value: ToastContextType = {
        toasts: state.toasts,
        defaultPosition: state.defaultPosition,
        addToast,
        removeToast,
        clearAllToasts,
        setDefaultPosition,
        success,
        error,
        warning,
        info,
    };

    return <ToastContext.Provider value={value}>{children}</ToastContext.Provider>;
};

export { ToastContext };
