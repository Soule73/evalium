import { useState, useCallback } from 'react';

interface UseFormProps<T> {
    initialValues: T;
    onSubmit?: (values: T) => void | Promise<void>;
    validate?: (values: T) => Partial<Record<keyof T, string>>;
}

interface UseFormReturn<T> {
    values: T;
    data: T; // Alias pour compatibilité
    errors: Partial<Record<keyof T, string>>;
    isSubmitting: boolean;
    processing: boolean; // Alias pour compatibilité
    handleChange: (field: keyof T) => (value: any) => void;
    handleSubmit: (e: React.FormEvent) => void;
    setFieldValue: (field: keyof T, value: any) => void;
    setData: (values: T | ((prev: T) => T)) => void; // Alias pour compatibilité
    setFieldError: (field: keyof T, error: string) => void;
    reset: () => void;
}

// Version simple pour les données simples
export function useForm<T extends Record<string, any>>(
    initialValues: T
): UseFormReturn<T>;

// Version complète avec configuration
export function useForm<T extends Record<string, any>>(
    config: UseFormProps<T>
): UseFormReturn<T>;

export function useForm<T extends Record<string, any>>(
    configOrValues: UseFormProps<T> | T
): UseFormReturn<T> {
    // Déterminer si c'est un objet de configuration ou des valeurs directes
    const isConfig = configOrValues && typeof configOrValues === 'object' && 'initialValues' in configOrValues;
    const initialValues = isConfig ? configOrValues.initialValues : configOrValues;
    const onSubmit = isConfig ? configOrValues.onSubmit : undefined;
    const validate = isConfig ? configOrValues.validate : undefined;
    const [values, setValues] = useState<T>(initialValues);
    const [errors, setErrors] = useState<Partial<Record<keyof T, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = useCallback((field: keyof T) => (value: any) => {
        setValues(prev => ({
            ...prev,
            [field]: value,
        }));

        // Clear error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: undefined,
            }));
        }
    }, [errors]);

    const setFieldValue = useCallback((field: keyof T, value: any) => {
        setValues(prev => ({
            ...prev,
            [field]: value,
        }));
    }, []);

    const setFieldError = useCallback((field: keyof T, error: string) => {
        setErrors(prev => ({
            ...prev,
            [field]: error,
        }));
    }, []);

    const handleSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();

        if (isSubmitting) return;

        // Validate form
        if (validate) {
            const validationErrors = validate(values);
            setErrors(validationErrors);

            if (Object.keys(validationErrors).length > 0) {
                return;
            }
        }

        setIsSubmitting(true);

        try {
            await onSubmit(values);
        } catch (error) {
            console.error('Form submission error:', error);
        } finally {
            setIsSubmitting(false);
        }
    }, [values, validate, onSubmit, isSubmitting]);

    const setData = useCallback((newValues: T | ((prev: T) => T)) => {
        if (typeof newValues === 'function') {
            setValues(prev => newValues(prev));
        } else {
            setValues(newValues);
        }
    }, []);

    const reset = useCallback(() => {
        setValues(initialValues);
        setErrors({});
        setIsSubmitting(false);
    }, [initialValues]);

    return {
        values,
        data: values, // Alias pour compatibilité
        errors,
        isSubmitting,
        processing: isSubmitting, // Alias pour compatibilité
        handleChange,
        handleSubmit,
        setFieldValue,
        setData,
        setFieldError,
        reset,
    };
}