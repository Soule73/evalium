import { useMemo, useCallback, memo } from 'react';
import { type DataTableProps } from '@/types/datatable';
import { DataTableFilters } from './DataTableFilters';
import { DataTablePagination } from './DataTablePagination';
import { EmptyState } from '../EmptyState';
import { BulkActions } from './BulkActions';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Checkbox } from '@examena/ui';
import { useDataTable } from '@/hooks';

function DataTableInner<T extends { id: number | string }>({
    data,
    config,
    onStateChange,
    onSelectionChange,
    isLoading = false,
    className = '',
    testIdSelectAllCheckbox = 'datatable-select-all-checkbox',
    testIdTableBody = 'datatable-table-body',
    testIdEmptyState = 'datatable-empty-state',
    testIdResetFiltersButton = 'datatable-reset-filters-button',
    testIdEmptyResetFiltersButton,
    dataTableSearchInputId

}: DataTableProps<T>) {
    const { t } = useTranslations();
    const { state, actions, isNavigating, selection } = useDataTable(data, {
        enableSelection: config.enableSelection,
        maxSelectable: config.maxSelectable,
        isSelectable: config.isSelectable,
        filters: config.filters,
        onStateChange,
        onSelectionChange
    });

    const hasActiveFilters = useMemo(
        () => state.search || Object.values(state.filters).some(v => v),
        [state.search, state.filters]
    );
    const isEmpty = data.data.length === 0;
    const showEmptyState = isEmpty && !isLoading;
    const showEmptySearchState = showEmptyState && hasActiveFilters;
    const combinedLoading = isLoading || isNavigating;

    const showFilters = !!(config.filters?.length || config.searchPlaceholder);

    const handleResetFilters = useCallback(() => {
        actions.resetFilters();
    }, [actions]);

    return (
        <div className={`bg-white rounded-lg overflow-hidden ${className}`}>
            {showFilters && (
                <DataTableFilters
                    filters={config.filters || []}
                    values={state.filters}
                    searchValue={state.search}
                    searchPlaceholder={config.searchPlaceholder}
                    onSearchChange={actions.setSearch}
                    onFilterChange={actions.setFilter}
                    onReset={handleResetFilters}
                    isLoading={combinedLoading}
                    testIdResetFiltersButton={testIdResetFiltersButton}
                    dataTableSearchInputId={dataTableSearchInputId}
                />
            )}

            {config.enableSelection && selection.selectedCount > 0 && (
                <BulkActions
                    selectedCount={selection.selectedCount}
                    onDeselectAll={actions.deselectAll}
                >
                    {config.selectionActions?.(actions.getSelectedItems())}
                </BulkActions>
            )}

            <div className="relative">
                {showEmptyState ? (
                    showEmptySearchState && config.emptySearchState ? (
                        <EmptyState
                            data-e2e={testIdEmptyState}
                            title={config.emptySearchState.title}
                            subtitle={config.emptySearchState.subtitle}
                            icon={config.emptySearchState.icon}
                            actions={
                                <button
                                    data-e2e={testIdEmptyResetFiltersButton}
                                    onClick={handleResetFilters}
                                    className="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    {config.emptySearchState.resetLabel || t('components.datatable.reset_filters_default')}
                                </button>
                            }
                        />
                    ) : config.emptyState ? (
                        <EmptyState
                            title={config.emptyState.title}
                            subtitle={config.emptyState.subtitle}
                            icon={config.emptyState.icon}
                            actions={config.emptyState.actions}
                        />
                    ) : null
                ) : (
                    <div className="overflow-x-auto custom-scrollbar">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {config.enableSelection && (
                                        <th className="px-6 py-3 text-left w-12">
                                            <Checkbox
                                                checked={selection.allItemsOnPageSelected}
                                                ref={(el) => {
                                                    if (el) {
                                                        el.indeterminate = selection.someItemsOnPageSelected;
                                                    }
                                                }}
                                                onChange={actions.toggleAllOnPage}
                                                className="cursor-pointer"
                                                aria-label={t('components.datatable.select_all')}
                                                data-e2e={testIdSelectAllCheckbox}
                                            />
                                        </th>
                                    )}
                                    {config.columns.map((column) => (
                                        <th
                                            key={column.key}
                                            data-e2e={`datatable-header-${column.key}`}
                                            className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${column.className || ''}`}
                                        >
                                            {column.label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200" data-e2e={testIdTableBody}>
                                {data.data.map((item, index) => {
                                    const isItemSelectable = !config.isSelectable || config.isSelectable(item);

                                    return (
                                        <tr key={item.id ?? index} className="hover:bg-gray-50">
                                            {config.enableSelection && item.id !== undefined && (
                                                <td className="px-6 py-4 whitespace-nowrap w-12">
                                                    <Checkbox
                                                        checked={actions.isItemSelected(item.id)}
                                                        onChange={() => actions.toggleItem(item.id!)}
                                                        disabled={!isItemSelectable}
                                                        className={isItemSelectable ? "cursor-pointer" : "cursor-not-allowed opacity-50"}
                                                        aria-label={t('components.datatable.select_item', { id: String(item.id) })}
                                                        data-e2e={`datatable-row-checkbox-${item.id}`}
                                                    />
                                                </td>
                                            )}
                                            {config.columns.map((column) => (
                                                <td
                                                    key={column.key}
                                                    className={`px-6 py-4 whitespace-nowrap ${column.className || ''}`}
                                                >
                                                    {column.render ? (
                                                        column.render(item, index)
                                                    ) : (
                                                        <span className="text-sm text-gray-900">
                                                            {String((item as Record<string, unknown>)[column.key] || '')}
                                                        </span>
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {!showEmptyState && config.showPagination !== false && (
                <DataTablePagination
                    data={data}
                    onPageChange={actions.goToPage}
                    onPerPageChange={actions.setPerPage}
                    isLoading={combinedLoading}
                    perPageOptions={config.perPageOptions}
                />
            )}
        </div>
    );
}

export const DataTable = memo(DataTableInner) as typeof DataTableInner;