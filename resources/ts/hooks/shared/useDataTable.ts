import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { router } from '@inertiajs/react';
import { type DataTableState, type PaginationType, type FilterConfig } from '@/types/datatable';
import {
    buildDataTableUrl,
    areAllSelectableItemsSelected,
    areSomeSelectableItemsSelected,
    toggleAllPageSelection,
    toggleItemSelection,
    selectAllItems,
} from '@/utils';

interface UseDataTableOptions<T> {
    initialState?: Partial<DataTableState>;
    preserveState?: boolean;
    debounceMs?: number;
    enableSelection?: boolean;
    maxSelectable?: number;
    isSelectable?: (item: T) => boolean;
    filters?: FilterConfig[];
    onStateChange?: (state: DataTableState) => void;
    onSelectionChange?: (selectedIds: (number | string)[]) => void;
    selectedIds?: (number | string)[];
    /** When true, disables URL navigation (router.visit). Intended for list/static mode. */
    isStatic?: boolean;
}

/**
 * Custom hook for managing data table state, navigation, filtering, and selection
 */
export function useDataTable<T extends { id: number | string }>(
    data: PaginationType<T>,
    options: UseDataTableOptions<T> = {},
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
        onSelectionChange,
        selectedIds,
        isStatic = false,
    } = options;

    const [initialized, setInitialized] = useState(false);
    const [state, setState] = useState<DataTableState>(() => {
        const base: DataTableState = {
            search: initialState.search || '',
            filters: initialState.filters || {},
            page: data.current_page,
            perPage: data.per_page,
            ...initialState,
        };

        if (typeof window === 'undefined') return base;

        const params = new URLSearchParams(window.location.search);
        const search = params.get('search');
        if (search) base.search = search;

        if (filters) {
            filters.forEach((filter) => {
                const value = params.get(filter.key);
                if (value !== null) {
                    base.filters = { ...base.filters, [filter.key]: value };
                }
            });
        }

        const page = params.get('page');
        if (page && !isNaN(Number(page))) base.page = Number(page);

        const perPage = params.get('per_page');
        if (perPage && !isNaN(Number(perPage))) base.perPage = Number(perPage);

        return base;
    });

    const [isNavigating, setIsNavigating] = useState(false);
    const [selectedItems, setSelectedItems] = useState<Set<number | string>>(new Set());
    const debounceTimerRef = useRef<NodeJS.Timeout | null>(null);
    const onStateChangeRef = useRef(onStateChange);
    const onSelectionChangeRef = useRef(onSelectionChange);
    const lastReportedSelectionKeyRef = useRef<string>('');

    onStateChangeRef.current = onStateChange;
    onSelectionChangeRef.current = onSelectionChange;

    useEffect(() => {
        if (!initialized) {
            setInitialized(true);
        }
    }, [initialized]);

    useEffect(() => {
        if (enableSelection) {
            setSelectedItems(new Set());
        }
    }, [data.current_page, enableSelection]);

    useEffect(() => {
        if (!selectedIds || !enableSelection) return;
        const incomingKey = [...selectedIds].sort().join(',');
        if (incomingKey === lastReportedSelectionKeyRef.current) return;
        lastReportedSelectionKeyRef.current = incomingKey;
        setSelectedItems(new Set(selectedIds));
    }, [selectedIds, enableSelection]);

    useEffect(() => {
        setState((prev) => {
            if (prev.page === data.current_page && prev.perPage === data.per_page) {
                return prev;
            }
            return { ...prev, page: data.current_page, perPage: data.per_page };
        });
    }, [data.current_page, data.per_page]);

    useEffect(() => {
        onStateChangeRef.current?.(state);
    }, [state]);

    useEffect(() => {
        const arr = Array.from(selectedItems);
        lastReportedSelectionKeyRef.current = [...arr].sort().join(',');
        onSelectionChangeRef.current?.(arr);
    }, [selectedItems]);

    const dataPath = data.path;

    const buildUrl = useCallback(
        (newState: Partial<DataTableState> = {}, basePath?: string) => {
            const finalState = { ...state, ...newState };
            const path = basePath || dataPath;
            return buildDataTableUrl(finalState, path);
        },
        [state, dataPath],
    );

    const navigate = useCallback(
        (
            newState: Partial<DataTableState> = {},
            navOptions: { replace?: boolean; preserveState?: boolean } = {},
        ) => {
            if (isStatic) {
                return;
            }

            const url = buildUrl(newState);
            setIsNavigating(true);

            router.get(
                url,
                {},
                {
                    preserveState: navOptions.preserveState ?? preserveState,
                    replace: navOptions.replace ?? true,
                    onFinish: () => setIsNavigating(false),
                },
            );
        },
        [buildUrl, preserveState, isStatic],
    );

    const debouncedNavigate = useCallback(
        (newState: Partial<DataTableState>, immediate = false) => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }

            if (immediate) {
                navigate(newState);
                return;
            }

            debounceTimerRef.current = setTimeout(() => {
                navigate(newState);
            }, debounceMs);
        },
        [navigate, debounceMs],
    );

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

    const toggleItem = useCallback(
        (id: number | string) => {
            setSelectedItems((prev) =>
                toggleItemSelection(id, data.data, prev, maxSelectable, isSelectable),
            );
        },
        [data.data, maxSelectable, isSelectable],
    );

    const toggleAllOnPage = useCallback(() => {
        setSelectedItems((prev) =>
            toggleAllPageSelection(data.data, prev, maxSelectable, isSelectable),
        );
    }, [data.data, maxSelectable, isSelectable]);

    const selectAll = useCallback(() => {
        setSelectedItems(selectAllItems(data.data, maxSelectable, isSelectable));
    }, [data.data, maxSelectable, isSelectable]);

    const deselectAll = useCallback(() => {
        setSelectedItems(new Set());
    }, []);

    const isItemSelected = useCallback(
        (id: number | string) => {
            return selectedItems.has(id);
        },
        [selectedItems],
    );

    const getSelectedItems = useCallback(() => {
        return Array.from(selectedItems);
    }, [selectedItems]);

    const getSelectedCount = useCallback(() => {
        return selectedItems.size;
    }, [selectedItems]);

    const setSearch = useCallback(
        (search: string) => {
            setState((prev) => ({ ...prev, search }));
            debouncedNavigate({ search, page: 1 });
        },
        [debouncedNavigate],
    );

    const setFilter = useCallback(
        (key: string, value: string) => {
            setState((prev) => {
                const newFilters = { ...prev.filters, [key]: value };
                return { ...prev, filters: newFilters };
            });
            debouncedNavigate({
                filters: { ...state.filters, [key]: value },
                page: 1,
            });
        },
        [state.filters, debouncedNavigate],
    );

    const setFilters = useCallback(
        (newFilters: Record<string, string>) => {
            setState((prev) => ({ ...prev, filters: newFilters }));
            debouncedNavigate({ filters: newFilters, page: 1 });
        },
        [debouncedNavigate],
    );

    const resetFilters = useCallback(() => {
        const newState = { search: '', filters: {}, page: 1 };
        setState((prev) => ({ ...prev, ...newState }));
        navigate(newState, { replace: true });
    }, [navigate]);

    const goToPage = useCallback(
        (page: number) => {
            setState((prev) => ({ ...prev, page }));
            navigate({ page });
        },
        [navigate],
    );

    const setPerPage = useCallback(
        (perPage: number) => {
            setState((prev) => ({ ...prev, perPage, page: 1 }));
            navigate({ perPage, page: 1 });
        },
        [navigate],
    );

    const navigateToState = useCallback(
        (newState: Partial<DataTableState>, immediate = false) => {
            setState((prev) => ({ ...prev, ...newState }));
            if (immediate) {
                navigate(newState);
            } else {
                debouncedNavigate(newState);
            }
        },
        [navigate, debouncedNavigate],
    );

    const actions = useMemo(
        () => ({
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
            getSelectedCount,
        }),
        [
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
            getSelectedCount,
        ],
    );

    useEffect(() => {
        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    const selection = useMemo(
        () => ({
            selectedItems,
            allItemsOnPageSelected,
            someItemsOnPageSelected,
            selectedCount: selectedItems.size,
        }),
        [selectedItems, allItemsOnPageSelected, someItemsOnPageSelected],
    );

    return {
        state,
        actions,
        isNavigating,
        buildUrl,
        selection,
    };
}
