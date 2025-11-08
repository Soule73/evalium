import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';

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
        router.post(routeName, data as any, {
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
