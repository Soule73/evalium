/**
 * Base interface for API Resources with context awareness
 */
export interface ResourceBase {
    id: number;
    created_at: string;
    updated_at: string;
}

/**
 * Context variants for API Resources
 */
export type ResourceContext = 'admin' | 'teacher' | 'student' | 'full';

/**
 * Helper to check if a field exists in a resource
 */
export function hasField<T, K extends keyof T>(
    obj: T,
    field: K,
): obj is T & Record<K, NonNullable<T[K]>> {
    return obj[field] !== undefined && obj[field] !== null;
}
