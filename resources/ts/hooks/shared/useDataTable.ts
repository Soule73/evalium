import { useState, useEffect, useCallback, useMemo } from 'react';
import { router } from '@inertiajs/react';
import { DataTableState, PaginationType } from '@/types/datatable';
import {
    buildDataTableUrl,
    areAllSelectableItemsSelected,
    areSomeSelectableItemsSelected,
    toggleAllPageSelection,
    toggleItemSelection,
    selectAllItems
} from '@/utils';

interface UseDataTableOptions {
    initialState?: Partial<DataTableState>;
    preserveState?: boolean;
    debounceMs?: number;
    enableSelection?: boolean;
    maxSelectable?: number;
    isSelectable?: (item: any) => boolean;
}

/**
 * Custom hook for managing data table state, navigation, filtering, and selection
 */
export function useDataTable<T extends { id: number | string }>(
    data: PaginationType<T>,
    options: UseDataTableOptions = {}
) {
    const {
        initialState = {},
        preserveState = true,
        debounceMs = 300,
        enableSelection = false,
        maxSelectable,
        isSelectable
    } = options;

    const [state, setState] = useState<DataTableState>({
        search: initialState.search || '',
        filters: initialState.filters || {},
        page: data.current_page,
        perPage: data.per_page,
        ...initialState
    });

    const [isNavigating, setIsNavigating] = useState(false);
    const [selectedItems, setSelectedItems] = useState<Set<number | string>>(new Set());

    useEffect(() => {
        if (enableSelection) {
            setSelectedItems(new Set());
        }
    }, [data.current_page, enableSelection]);

    useEffect(() => {
        setState(prev => ({
            ...prev,
            page: data.current_page,
            perPage: data.per_page
        }));
    }, [data.current_page, data.per_page]);

    /**
     * Builds a URL with the current or new state parameters
     */
    const buildUrl = useCallback((
        newState: Partial<DataTableState> = {},
        basePath?: string
    ) => {
        const finalState = { ...state, ...newState };
        const path = basePath || data.path;
        return buildDataTableUrl(finalState, path);
    }, [state, data.path]);

    /**
     * Navigates to a new state by updating the URL and triggering Inertia navigation
     */
    const navigate = useCallback((
        newState: Partial<DataTableState> = {},
        options: { replace?: boolean; preserveState?: boolean } = {}
    ) => {
        const url = buildUrl(newState);
        setIsNavigating(true);

        router.get(url, {}, {
            preserveState: options.preserveState ?? preserveState,
            replace: options.replace ?? true,
            onFinish: () => setIsNavigating(false)
        });
    }, [buildUrl, preserveState]);

    const [debounceTimer, setDebounceTimer] = useState<NodeJS.Timeout | null>(null);

    /**
     * Returns true if all selectable items on the current page are selected
     */
    const allItemsOnPageSelected = useMemo(() => {
        if (!enableSelection || data.data.length === 0) return false;
        return areAllSelectableItemsSelected(data.data, selectedItems, isSelectable);
    }, [selectedItems, data.data, enableSelection, isSelectable]);

    /**
     * Returns true if some but not all selectable items on the current page are selected
     */
    const someItemsOnPageSelected = useMemo(() => {
        if (!enableSelection || data.data.length === 0) return false;
        return areSomeSelectableItemsSelected(data.data, selectedItems, isSelectable);
    }, [selectedItems, data.data, enableSelection, isSelectable]);

    /**
     * Debounces navigation to avoid excessive API calls during rapid state changes
     */
    const debouncedNavigate = useCallback((
        newState: Partial<DataTableState>,
        immediate = false
    ) => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        if (immediate) {
            navigate(newState);
            return;
        }

        const timer = setTimeout(() => {
            navigate(newState);
        }, debounceMs);

        setDebounceTimer(timer);
    }, [navigate, debounceTimer, debounceMs]);

    const actions = useMemo(() => ({
        setSearch: (search: string) => {
            setState(prev => ({ ...prev, search }));
            debouncedNavigate({ search, page: 1 });
        },

        setFilter: (key: string, value: string) => {
            setState(prev => ({
                ...prev,
                filters: { ...prev.filters, [key]: value }
            }));
            debouncedNavigate({
                filters: { ...state.filters, [key]: value },
                page: 1
            });
        },

        setFilters: (filters: Record<string, string>) => {
            setState(prev => ({ ...prev, filters }));
            debouncedNavigate({ filters, page: 1 });
        },

        resetFilters: () => {
            const newState = { search: '', filters: {}, page: 1 };
            setState(prev => ({ ...prev, ...newState }));
            navigate(newState, { replace: true });
        },

        goToPage: (page: number) => {
            setState(prev => ({ ...prev, page }));
            navigate({ page });
        },

        setPerPage: (perPage: number) => {
            setState(prev => ({ ...prev, perPage, page: 1 }));
            navigate({ perPage, page: 1 });
        },

        navigateToState: (newState: Partial<DataTableState>, immediate = false) => {
            setState(prev => ({ ...prev, ...newState }));
            if (immediate) {
                navigate(newState);
            } else {
                debouncedNavigate(newState);
            }
        },

        toggleItem: (id: number | string) => {
            setSelectedItems(prev =>
                toggleItemSelection(id, data.data, prev, maxSelectable, isSelectable)
            );
        },

        toggleAllOnPage: () => {
            setSelectedItems(prev =>
                toggleAllPageSelection(data.data, prev, maxSelectable, isSelectable)
            );
        },

        selectAll: () => {
            setSelectedItems(selectAllItems(data.data, maxSelectable, isSelectable));
        },

        deselectAll: () => {
            setSelectedItems(new Set());
        },

        isItemSelected: (id: number | string) => selectedItems.has(id),

        getSelectedItems: () => Array.from(selectedItems),

        getSelectedCount: () => selectedItems.size
    }), [state, navigate, debouncedNavigate, selectedItems, data.data, maxSelectable, isSelectable]);

    useEffect(() => {
        return () => {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
        };
    }, [debounceTimer]);

    return {
        state,
        actions,
        isNavigating,
        buildUrl,
        selection: {
            selectedItems,
            allItemsOnPageSelected,
            someItemsOnPageSelected,
            selectedCount: selectedItems.size
        }
    };
}