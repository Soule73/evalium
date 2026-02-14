/**
 * Validation utilities for forms and data
 *
 * Only pure validation patterns and helpers remain here.
 */

export interface ValidationRule {
    required?: boolean;
    minLength?: number;
    maxLength?: number;
    pattern?: RegExp;
    min?: number;
    max?: number;
    custom?: (value: unknown) => string | null;
}

export interface ValidationSchema {
    [key: string]: ValidationRule;
}

export interface ValidationErrors {
    [key: string]: string;
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

export function isValidEmail(email: string): boolean {
    return validationPatterns.email.test(email);
}

export function isStrongPassword(password: string): boolean {
    return validationPatterns.strongPassword.test(password);
}

export function sanitizeInput(input: string): string {
    return input.trim().replace(/[<>]/g, '');
}