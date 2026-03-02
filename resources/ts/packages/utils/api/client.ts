/**
 * HTTP client utilities with proper error handling
 */

import { router } from '@inertiajs/react';

interface RequestOptions extends RequestInit {
    params?: Record<string, string | number | boolean>;
}

class ApiError extends Error {
    public status: number;
    public data?: unknown;

    constructor(message: string, status: number, data?: unknown) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

/**
 * Get CSRF token from meta tag
 */
function getCsrfToken(): string {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        throw new Error('CSRF token not found');
    }
    return token;
}

/**
 * Build URL with query parameters
 */
function buildUrl(url: string, params?: Record<string, string | number | boolean>): string {
    if (!params) return url;

    const searchParams = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        searchParams.append(key, String(value));
    }

    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}${searchParams.toString()}`;
}

/**
 * Make HTTP request with proper error handling
 */
async function makeRequest<T>(url: string, options: RequestOptions = {}): Promise<T> {
    const { params, headers = {}, ...fetchOptions } = options;

    const finalUrl = buildUrl(url, params);

    const defaultHeaders: Record<string, string> = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
        ...(headers as Record<string, string>),
    };

    try {
        const response = await fetch(finalUrl, {
            ...fetchOptions,
            headers: defaultHeaders,
        });

        let data: unknown;
        const contentType = response.headers.get('Content-Type');

        if (contentType?.includes('application/json')) {
            data = await response.json();
        } else if (contentType?.includes('text/')) {
            data = await response.text();
        } else {
            data = await response.blob();
        }

        if (!response.ok) {
            // Handle authentication errors
            if (response.status === 401) {
                router.visit('/login');
                throw new ApiError('Session expired', 401, data);
            }

            if (response.status === 422) {
                throw new ApiError('Validation error', 422, data);
            }

            const errorMessage =
                typeof data === 'object' && data !== null && 'message' in data
                    ? String((data as { message: unknown }).message)
                    : `HTTP ${response.status}: ${response.statusText}`;

            throw new ApiError(errorMessage, response.status, data);
        }

        return data as T;
    } catch (error) {
        if (error instanceof ApiError) {
            throw error;
        }

        // Handle network errors
        if (error instanceof TypeError && error.message.includes('fetch')) {
            throw new ApiError('Network error', 0, { originalError: error });
        }

        throw new ApiError('Unknown error', 0, { originalError: error });
    }
}

/**
 * HTTP client API
 */
export const api = {
    get: <T>(url: string, options: Omit<RequestOptions, 'method' | 'body'> = {}) =>
        makeRequest<T>(url, { ...options, method: 'GET' }),

    post: <T>(url: string, data?: unknown, options: Omit<RequestOptions, 'method'> = {}) =>
        makeRequest<T>(url, {
            ...options,
            method: 'POST',
            body: data ? JSON.stringify(data) : undefined,
        }),

    put: <T>(url: string, data?: unknown, options: Omit<RequestOptions, 'method'> = {}) =>
        makeRequest<T>(url, {
            ...options,
            method: 'PUT',
            body: data ? JSON.stringify(data) : undefined,
        }),

    patch: <T>(url: string, data?: unknown, options: Omit<RequestOptions, 'method'> = {}) =>
        makeRequest<T>(url, {
            ...options,
            method: 'PATCH',
            body: data ? JSON.stringify(data) : undefined,
        }),

    delete: <T>(url: string, options: Omit<RequestOptions, 'method' | 'body'> = {}) =>
        makeRequest<T>(url, { ...options, method: 'DELETE' }),

    upload: <T>(
        url: string,
        formData: FormData,
        options: Omit<RequestOptions, 'method' | 'body'> = {},
    ) => {
        const { headers = {}, ...restOptions } = options;
        return makeRequest<T>(url, {
            ...restOptions,
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                ...(headers as Record<string, string>),
            },
        });
    },
};

/**
 * Export error class for use in components
 */
export { ApiError };

export function handleApiError(error: unknown): string {
    if (error instanceof ApiError) {
        if (
            error.status === 422 &&
            error.data &&
            typeof error.data === 'object' &&
            'errors' in error.data
        ) {
            const errors = (error.data as { errors: Record<string, string | string[]> }).errors;
            const firstError = Object.values(errors)[0];
            return Array.isArray(firstError) ? firstError[0] : String(firstError);
        }
        return error.message;
    }

    if (error instanceof Error) {
        return error.message;
    }

    return "Une erreur inattendue s'est produite";
}

/**
 * Utility to check if user is online
 */
export function isOnline(): boolean {
    return navigator.onLine;
}

/**
 * Retry wrapper for API calls
 */
export async function withRetry<T>(
    fn: () => Promise<T>,
    maxRetries: number = 3,
    delay: number = 1000,
): Promise<T> {
    let lastError: Error;

    for (let i = 0; i <= maxRetries; i++) {
        try {
            return await fn();
        } catch (error) {
            lastError = error as Error;

            if (i === maxRetries) break;

            // Don't retry on client errors (4xx)
            if (error instanceof ApiError && error.status >= 400 && error.status < 500) {
                break;
            }

            // Exponential backoff
            await new Promise((resolve) => setTimeout(resolve, delay * Math.pow(2, i)));
        }
    }

    throw lastError!;
}
