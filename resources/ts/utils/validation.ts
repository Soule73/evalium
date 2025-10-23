/**
 * Validation utilities for forms and data
 */

export interface ValidationRule {
    required?: boolean;
    minLength?: number;
    maxLength?: number;
    pattern?: RegExp;
    min?: number;
    max?: number;
    custom?: (value: any) => string | null;
}

export interface ValidationSchema {
    [key: string]: ValidationRule;
}

export interface ValidationErrors {
    [key: string]: string;
}

export function validateField(value: any, rules: ValidationRule): string | null {
    // Required validation
    if (rules.required && (!value || (typeof value === 'string' && value.trim() === ''))) {
        return 'Ce champ est requis';
    }

    // Skip other validations if value is empty and not required
    if (!value && !rules.required) {
        return null;
    }

    // String validations
    if (typeof value === 'string') {
        if (rules.minLength && value.length < rules.minLength) {
            return `Minimum ${rules.minLength} caractères requis`;
        }

        if (rules.maxLength && value.length > rules.maxLength) {
            return `Maximum ${rules.maxLength} caractères autorisés`;
        }

        if (rules.pattern && !rules.pattern.test(value)) {
            return 'Format invalide';
        }
    }

    // Number validations
    if (typeof value === 'number') {
        if (rules.min !== undefined && value < rules.min) {
            return `La valeur doit être au moins ${rules.min}`;
        }

        if (rules.max !== undefined && value > rules.max) {
            return `La valeur ne peut pas dépasser ${rules.max}`;
        }
    }

    // Custom validation
    if (rules.custom) {
        return rules.custom(value);
    }

    return null;
}

export function validateForm<T extends Record<string, any>>(
    data: T,
    schema: ValidationSchema
): ValidationErrors {
    const errors: ValidationErrors = {};

    for (const [field, rules] of Object.entries(schema)) {
        const error = validateField(data[field], rules);
        if (error) {
            errors[field] = error;
        }
    }

    return errors;
}

// Common validation patterns
export const validationPatterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    phone: /^(?:\+33|0)[1-9](?:[0-9]{8})$/,
    strongPassword: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
    alphanumeric: /^[a-zA-Z0-9]+$/,
    alphanumericWithSpaces: /^[a-zA-Z0-9\s]+$/,
    frenchPostalCode: /^(?:0[1-9]|[1-8]\d|9[0-8])\d{3}$/,
};

// Pre-defined validation schemas
export const commonValidationSchemas = {
    user: {
        name: {
            required: true,
            minLength: 2,
            maxLength: 100,
            pattern: validationPatterns.alphanumericWithSpaces,
        },
        email: {
            required: true,
            pattern: validationPatterns.email,
        },
        password: {
            required: true,
            minLength: 8,
            pattern: validationPatterns.strongPassword,
        },
    },
    exam: {
        title: {
            required: true,
            minLength: 3,
            maxLength: 200,
        },
        description: {
            maxLength: 1000,
        },
        duration: {
            required: true,
            min: 1,
            max: 480, // 8 hours max
        },
        max_attempts: {
            required: true,
            min: 1,
            max: 10,
        },
    },
    question: {
        question_text: {
            required: true,
            minLength: 10,
            maxLength: 2000,
        },
        points: {
            required: true,
            min: 1,
            max: 100,
        },
        type: {
            required: true,
            custom: (value: string) => {
                const validTypes = ['multiple', 'text'];
                return validTypes.includes(value) ? null : 'Type de question invalide';
            },
        },
    },
};

// Utility functions for validation
export function isValidEmail(email: string): boolean {
    return validationPatterns.email.test(email);
}

export function isStrongPassword(password: string): boolean {
    return validationPatterns.strongPassword.test(password);
}

export function sanitizeInput(input: string): string {
    return input.trim().replace(/[<>]/g, '');
}

export function validateFileUpload(
    file: File,
    allowedTypes: string[] = [],
    maxSize: number = 5 * 1024 * 1024 // 5MB
): string | null {
    if (!file) {
        return 'Aucun fichier sélectionné';
    }

    if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
        return `Type de fichier non autorisé. Types acceptés: ${allowedTypes.join(', ')}`;
    }

    if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024));
        return `Fichier trop volumineux. Taille maximale: ${maxSizeMB}MB`;
    }

    return null;
}