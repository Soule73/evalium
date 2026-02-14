import { type DataTableState } from '@/types/datatable';

/**
 * Builds a URL with query parameters based on the current data table state
 */
export function buildDataTableUrl(
    state: DataTableState,
    basePath: string
): string {
    const params = new URLSearchParams();

    params.set('page', String(state.page));
    params.set('per_page', String(state.perPage));

    if (state.search.trim()) {
        params.set('search', state.search.trim());
    }

    Object.entries(state.filters).forEach(([key, value]) => {
        if (value && value.trim()) {
            params.set(key, value.trim());
        }
    });

    return `${basePath}?${params.toString()}`;
}

/**
 * Filters items that can be selected based on the isSelectable predicate
 */
export function getSelectableItems<T>(
    items: T[],
    isSelectable?: (item: T) => boolean
): T[] {
    if (!isSelectable) {
        return items;
    }
    return items.filter(isSelectable);
}

/**
 * Checks if all selectable items in the list are currently selected
 */
export function areAllSelectableItemsSelected<T extends { id: number | string }>(
    items: T[],
    selectedIds: Set<number | string>,
    isSelectable?: (item: T) => boolean
): boolean {
    const selectableItems = getSelectableItems(items, isSelectable);

    if (selectableItems.length === 0) {
        return false;
    }

    return selectableItems.every(item => selectedIds.has(item.id));
}

/**
 * Checks if some but not all selectable items are currently selected
 */
export function areSomeSelectableItemsSelected<T extends { id: number | string }>(
    items: T[],
    selectedIds: Set<number | string>,
    isSelectable?: (item: T) => boolean
): boolean {
    const selectableItems = getSelectableItems(items, isSelectable);

    if (selectableItems.length === 0) {
        return false;
    }

    const hasSelected = selectableItems.some(item => selectedIds.has(item.id));
    const hasUnselected = selectableItems.some(item => !selectedIds.has(item.id));

    return hasSelected && hasUnselected;
}

/**
 * Checks if an item can be added to the selection
 */
export function canAddToSelection<T extends { id: number | string }>(
    item: T,
    currentSize: number,
    maxSelectable?: number,
    isSelectable?: (item: T) => boolean
): boolean {
    if (isSelectable && !isSelectable(item)) {
        return false;
    }

    if (maxSelectable && currentSize >= maxSelectable) {
        return false;
    }

    return true;
}

/**
 * Toggles the selection of all selectable items on the current page
 */
export function toggleAllPageSelection<T extends { id: number | string }>(
    items: T[],
    currentSelection: Set<number | string>,
    maxSelectable?: number,
    isSelectable?: (item: T) => boolean
): Set<number | string> {
    const newSet = new Set(currentSelection);
    const selectableItems = getSelectableItems(items, isSelectable);
    const allSelected = selectableItems.every(item => newSet.has(item.id));

    if (allSelected) {
        selectableItems.forEach(item => newSet.delete(item.id));
    } else {
        for (const item of selectableItems) {
            if (maxSelectable && newSet.size >= maxSelectable) {
                break;
            }
            newSet.add(item.id);
        }
    }

    return newSet;
}

/**
 * Creates a new selection set with all selectable items
 */
export function selectAllItems<T extends { id: number | string }>(
    items: T[],
    maxSelectable?: number,
    isSelectable?: (item: T) => boolean
): Set<number | string> {
    const newSet = new Set<number | string>();
    const selectableItems = getSelectableItems(items, isSelectable);
    const itemsToSelect = maxSelectable
        ? selectableItems.slice(0, maxSelectable)
        : selectableItems;

    itemsToSelect.forEach(item => newSet.add(item.id));

    return newSet;
}

/**
 * Toggles a single item's selection state
 */
export function toggleItemSelection<T extends { id: number | string }>(
    itemId: number | string,
    items: T[],
    currentSelection: Set<number | string>,
    maxSelectable?: number,
    isSelectable?: (item: T) => boolean
): Set<number | string> {
    const newSet = new Set(currentSelection);

    if (newSet.has(itemId)) {
        newSet.delete(itemId);
        return newSet;
    }

    const item = items.find(i => i.id === itemId);
    if (!item) {
        return currentSelection;
    }

    if (!canAddToSelection(item, newSet.size, maxSelectable, isSelectable)) {
        return currentSelection;
    }

    newSet.add(itemId);
    return newSet;
}
