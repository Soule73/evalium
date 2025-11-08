import { useState, useEffect, useCallback, useMemo } from 'react';
import { router } from '@inertiajs/react';
import { DataTableState, PaginationType, FilterConfig } from '@/types/datatable';
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
    filters?: FilterConfig[];
    onStateChange?: (state: DataTableState) => void;
    onSelectionChange?: (selectedIds: (number | string)[]) => void;
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
        isSelectable,
        filters,
        onStateChange,
        onSelectionChange
    } = options;

    const [initialized, setInitialized] = useState(false);
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
        if (!initialized) {
            const params = new URLSearchParams(window.location.search);
            let hasInit = false;

            const search = params.get('search');
            if (search) {
                setState(prev => ({ ...prev, search }));
                hasInit = true;
            }

            if (filters) {
                filters.forEach((filter) => {
                    const value = params.get(filter.key);
                    if (value !== null) {
                        setState(prev => ({
                            ...prev,
                            filters: { ...prev.filters, [filter.key]: value }
                        }));
                        hasInit = true;
                    }
                });
            }

            const page = params.get('page');
            if (page && !isNaN(Number(page))) {
                setState(prev => ({ ...prev, page: Number(page) }));
                hasInit = true;
            }

            const perPage = params.get('per_page');
            if (perPage && !isNaN(Number(perPage))) {
                setState(prev => ({ ...prev, perPage: Number(perPage) }));
                hasInit = true;
            }

            if (hasInit) setInitialized(true);
        }
    }, [initialized, filters]);

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

    useEffect(() => {
        if (onStateChange) {
            onStateChange(state);
        }
    }, [state, onStateChange]);

    useEffect(() => {
        if (onSelectionChange) {
            onSelectionChange(Array.from(selectedItems));
        }
    }, [selectedItems, onSelectionChange]);

    const [debounceTimer, setDebounceTimer] = useState<NodeJS.Timeout | null>(null);

    const buildUrl = useCallback((
        newState: Partial<DataTableState> = {},
        basePath?: string
    ) => {
        const finalState = { ...state, ...newState };
        const path = basePath || data.path;
        return buildDataTableUrl(finalState, path);
    }, [state, data.path]);

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

    /**
     * Returns true if all selectable items on the current page are selected
     */
    const allItemsOnPageSelected = useMemo(() => {
        if (!enableSelection || data.data.length === 0) return false;
        return areAllSelectableItemsSelected(data.data, selectedItems, isSelectable);
    }, [selectedItems, data.data, enableSelection, isSelectable]);

    const someItemsOnPageSelected = useMemo(() => {
        if (!enableSelection || data.data.length === 0) return false;
        return areSomeSelectableItemsSelected(data.data, selectedItems, isSelectable);
    }, [selectedItems, data.data, enableSelection, isSelectable]);

    const toggleItem = useCallback((id: number | string) => {
        setSelectedItems(prev =>
            toggleItemSelection(id, data.data, prev, maxSelectable, isSelectable)
        );
    }, [data.data, maxSelectable, isSelectable]);

    const toggleAllOnPage = useCallback(() => {
        setSelectedItems(prev =>
            toggleAllPageSelection(data.data, prev, maxSelectable, isSelectable)
        );
    }, [data.data, maxSelectable, isSelectable]);

    const selectAll = useCallback(() => {
        setSelectedItems(selectAllItems(data.data, maxSelectable, isSelectable));
    }, [data.data, maxSelectable, isSelectable]);

    const deselectAll = useCallback(() => {
        setSelectedItems(new Set());
    }, []);

    const isItemSelected = useCallback((id: number | string) => {
        return selectedItems.has(id);
    }, [selectedItems]);

    const getSelectedItems = useCallback(() => {
        return Array.from(selectedItems);
    }, [selectedItems]);

    const getSelectedCount = useCallback(() => {
        return selectedItems.size;
    }, [selectedItems]);

    const setSearch = useCallback((search: string) => {
        setState(prev => ({ ...prev, search }));
        debouncedNavigate({ search, page: 1 });
    }, [debouncedNavigate]);

    const setFilter = useCallback((key: string, value: string) => {
        setState(prev => ({
            ...prev,
            filters: { ...prev.filters, [key]: value }
        }));
        debouncedNavigate({
            filters: { ...state.filters, [key]: value },
            page: 1
        });
    }, [state.filters, debouncedNavigate]);

    const setFilters = useCallback((filters: Record<string, string>) => {
        setState(prev => ({ ...prev, filters }));
        debouncedNavigate({ filters, page: 1 });
    }, [debouncedNavigate]);

    const resetFilters = useCallback(() => {
        const newState = { search: '', filters: {}, page: 1 };
        setState(prev => ({ ...prev, ...newState }));
        navigate(newState, { replace: true });
    }, [navigate]);

    const goToPage = useCallback((page: number) => {
        setState(prev => ({ ...prev, page }));
        navigate({ page });
    }, [navigate]);

    const setPerPage = useCallback((perPage: number) => {
        setState(prev => ({ ...prev, perPage, page: 1 }));
        navigate({ perPage, page: 1 });
    }, [navigate]);

    const navigateToState = useCallback((newState: Partial<DataTableState>, immediate = false) => {
        setState(prev => ({ ...prev, ...newState }));
        if (immediate) {
            navigate(newState);
        } else {
            debouncedNavigate(newState);
        }
    }, [navigate, debouncedNavigate]);

    const actions = useMemo(() => ({
        setSearch,
        setFilter,
        setFilters,
        resetFilters,
        goToPage,
        setPerPage,
        navigateToState,
        toggleItem,
        toggleAllOnPage,
        selectAll,
        deselectAll,
        isItemSelected,
        getSelectedItems,
        getSelectedCount
    }), [
        setSearch,
        setFilter,
        setFilters,
        resetFilters,
        goToPage,
        setPerPage,
        navigateToState,
        toggleItem,
        toggleAllOnPage,
        selectAll,
        deselectAll,
        isItemSelected,
        getSelectedItems,
        getSelectedCount
    ]);

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