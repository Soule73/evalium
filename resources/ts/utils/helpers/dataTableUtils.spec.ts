import { describe, it, expect } from 'vitest';
import {
    buildDataTableUrl,
    getSelectableItems,
    areAllSelectableItemsSelected,
    areSomeSelectableItemsSelected,
    canAddToSelection,
    toggleAllPageSelection,
    selectAllItems,
    toggleItemSelection,
} from './dataTableUtils';

type TestItem = { id: number; name: string };

const items: TestItem[] = [
    { id: 1, name: 'Alpha' },
    { id: 2, name: 'Beta' },
    { id: 3, name: 'Gamma' },
];

describe('dataTableUtils', () => {
    describe('buildDataTableUrl', () => {
        it('should omit page when it equals 1', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 15, search: '', filters: {} },
                '/users',
            );
            const params = new URLSearchParams(url.split('?')[1]);
            expect(params.has('page')).toBe(false);
            expect(params.get('per_page')).toBe('15');
        });

        it('should include page when greater than 1', () => {
            const url = buildDataTableUrl(
                { page: 3, perPage: 15, search: '', filters: {} },
                '/users',
            );
            expect(url).toContain('page=3');
        });

        it('should include per_page in url', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 25, search: '', filters: {} },
                '/users',
            );
            expect(url).toContain('per_page=25');
        });

        it('should include search when not empty', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 15, search: 'test', filters: {} },
                '/users',
            );
            expect(url).toContain('search=test');
        });

        it('should exclude search when empty or whitespace', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 15, search: '   ', filters: {} },
                '/users',
            );
            expect(url).not.toContain('search');
        });

        it('should include non-empty filters', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 15, search: '', filters: { status: 'active', role: '' } },
                '/users',
            );
            expect(url).toContain('status=active');
            expect(url).not.toContain('role');
        });

        it('should produce a clean url with only per_page for default state', () => {
            const url = buildDataTableUrl(
                { page: 1, perPage: 15, search: '', filters: {} },
                '/admin/classes',
            );
            expect(url).toBe('/admin/classes?per_page=15');
        });
    });

    describe('getSelectableItems', () => {
        it('should return all items when no predicate is given', () => {
            expect(getSelectableItems(items)).toEqual(items);
        });

        it('should filter items using predicate', () => {
            const result = getSelectableItems(items, (item) => item.id !== 2);
            expect(result).toEqual([items[0], items[2]]);
        });
    });

    describe('areAllSelectableItemsSelected', () => {
        it('should return true when all selectable items are selected', () => {
            const selected = new Set<number | string>([1, 2, 3]);
            expect(areAllSelectableItemsSelected(items, selected)).toBe(true);
        });

        it('should return false when some items are not selected', () => {
            const selected = new Set<number | string>([1, 3]);
            expect(areAllSelectableItemsSelected(items, selected)).toBe(false);
        });

        it('should return false for empty items', () => {
            expect(areAllSelectableItemsSelected([], new Set())).toBe(false);
        });

        it('should consider isSelectable predicate', () => {
            const selected = new Set<number | string>([1, 3]);
            const onlyOdd = (item: TestItem) => item.id % 2 !== 0;
            expect(areAllSelectableItemsSelected(items, selected, onlyOdd)).toBe(true);
        });
    });

    describe('areSomeSelectableItemsSelected', () => {
        it('should return true when some but not all are selected', () => {
            const selected = new Set<number | string>([1]);
            expect(areSomeSelectableItemsSelected(items, selected)).toBe(true);
        });

        it('should return false when all are selected', () => {
            const selected = new Set<number | string>([1, 2, 3]);
            expect(areSomeSelectableItemsSelected(items, selected)).toBe(false);
        });

        it('should return false when none are selected', () => {
            expect(areSomeSelectableItemsSelected(items, new Set())).toBe(false);
        });
    });

    describe('canAddToSelection', () => {
        it('should return true when no constraints', () => {
            expect(canAddToSelection(items[0], 0)).toBe(true);
        });

        it('should return false when item is not selectable', () => {
            expect(canAddToSelection(items[0], 0, undefined, () => false)).toBe(false);
        });

        it('should return false when maxSelectable is reached', () => {
            expect(canAddToSelection(items[0], 5, 5)).toBe(false);
        });

        it('should return true when under maxSelectable', () => {
            expect(canAddToSelection(items[0], 2, 5)).toBe(true);
        });
    });

    describe('toggleAllPageSelection', () => {
        it('should select all when none are selected', () => {
            const result = toggleAllPageSelection(items, new Set());
            expect(result.size).toBe(3);
            expect(result.has(1)).toBe(true);
            expect(result.has(2)).toBe(true);
            expect(result.has(3)).toBe(true);
        });

        it('should deselect all when all are selected', () => {
            const result = toggleAllPageSelection(items, new Set([1, 2, 3]));
            expect(result.size).toBe(0);
        });

        it('should select remaining when some are selected', () => {
            const result = toggleAllPageSelection(items, new Set([1]));
            expect(result.size).toBe(3);
        });

        it('should respect maxSelectable', () => {
            const result = toggleAllPageSelection(items, new Set(), 2);
            expect(result.size).toBe(2);
        });
    });

    describe('selectAllItems', () => {
        it('should select all items', () => {
            const result = selectAllItems(items);
            expect(result.size).toBe(3);
        });

        it('should respect maxSelectable', () => {
            const result = selectAllItems(items, 2);
            expect(result.size).toBe(2);
        });

        it('should respect isSelectable', () => {
            const result = selectAllItems(items, undefined, (item) => item.id !== 2);
            expect(result.size).toBe(2);
            expect(result.has(2)).toBe(false);
        });
    });

    describe('toggleItemSelection', () => {
        it('should add item when not selected', () => {
            const result = toggleItemSelection(2, items, new Set([1]));
            expect(result.has(1)).toBe(true);
            expect(result.has(2)).toBe(true);
        });

        it('should remove item when already selected', () => {
            const result = toggleItemSelection(1, items, new Set([1, 2]));
            expect(result.has(1)).toBe(false);
            expect(result.has(2)).toBe(true);
        });

        it('should not add when maxSelectable reached', () => {
            const result = toggleItemSelection(3, items, new Set([1, 2]), 2);
            expect(result.size).toBe(2);
            expect(result.has(3)).toBe(false);
        });

        it('should return original set when item not found', () => {
            const original = new Set<number | string>([1]);
            const result = toggleItemSelection(99, items, original);
            expect(result).toBe(original);
        });

        it('should not add when item is not selectable', () => {
            const original = new Set<number | string>([1]);
            const result = toggleItemSelection(2, items, original, undefined, () => false);
            expect(result).toBe(original);
        });
    });
});
