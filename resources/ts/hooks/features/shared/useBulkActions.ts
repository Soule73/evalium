import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';

interface BulkActionOptions {
    onSuccess?: () => void;
    onError?: () => void;
}

export function useBulkActions() {
    const [selectedIds, setSelectedIds] = useState<(number | string)[]>([]);
    const [isLoading, setIsLoading] = useState(false);

    const executeBulkAction = useCallback((
        routeName: string,
        data: Record<string, unknown>,
        options?: BulkActionOptions
    ) => {
        setIsLoading(true);
        router.post(routeName, data as unknown as unknown as Record<string, FormDataConvertible>, {
            onSuccess: () => {
                setSelectedIds([]);
                setIsLoading(false);
                options?.onSuccess?.();
            },
            onError: () => {
                setIsLoading(false);
                options?.onError?.();
            }
        });
    }, []);

    const clearSelection = useCallback(() => {
        setSelectedIds([]);
    }, []);

    return {
        selectedIds,
        setSelectedIds,
        isLoading,
        executeBulkAction,
        clearSelection
    };
}
