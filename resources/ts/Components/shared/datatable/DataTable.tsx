import { DataTableProps } from '@/types/datatable';
import { DataTableFilters } from './DataTableFilters';
import { DataTablePagination } from './DataTablePagination';
import { EmptyState } from '../EmptyState';
import { BulkActions } from './BulkActions';
import { trans } from '@/utils';
import { Checkbox } from '@examena/ui';
import { useDataTable } from '@/hooks';

export function DataTable<T extends { id: number | string }>({
    data,
    config,
    onStateChange,
    onSelectionChange,
    isLoading = false,
    className = ''
}: DataTableProps<T>) {
    const { state, actions, isNavigating, selection } = useDataTable(data, {
        enableSelection: config.enableSelection,
        maxSelectable: config.maxSelectable,
        isSelectable: config.isSelectable,
        filters: config.filters,
        onStateChange,
        onSelectionChange
    });

    const hasActiveFilters = state.search || Object.values(state.filters).some(v => v);
    const isEmpty = data.data.length === 0;
    const showEmptyState = isEmpty && !isLoading;
    const showEmptySearchState = showEmptyState && hasActiveFilters;

    const renderTableHeader = () => (
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
                            aria-label={trans('components.datatable.select_all')}
                        />
                    </th>
                )}
                {config.columns.map((column) => (
                    <th
                        key={column.key}
                        className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${column.className || ''}`}
                    >
                        {column.label}
                    </th>
                ))}
            </tr>
        </thead>
    );

    const renderTableBody = () => (
        <tbody className="bg-white divide-y divide-gray-200">
            {data.data.map((item, index) => {
                const isItemSelectable = !config.isSelectable || config.isSelectable(item);

                return (
                    <tr key={index} className="hover:bg-gray-50">
                        {config.enableSelection && (
                            <td className="px-6 py-4 whitespace-nowrap w-12">
                                <Checkbox
                                    checked={actions.isItemSelected(item.id)}
                                    onChange={() => actions.toggleItem(item.id)}
                                    disabled={!isItemSelectable}
                                    className={isItemSelectable ? "cursor-pointer" : "cursor-not-allowed opacity-50"}
                                    aria-label={trans('components.datatable.select_item', { id: String(item.id) })}
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
                                        {String((item as any)[column.key] || '')}
                                    </span>
                                )}
                            </td>
                        ))}
                    </tr>
                );
            })}
        </tbody>
    );

    const renderEmptyState = () => {
        if (showEmptySearchState && config.emptySearchState) {
            return (
                <EmptyState
                    title={config.emptySearchState.title}
                    subtitle={config.emptySearchState.subtitle}
                    icon={config.emptySearchState.icon}
                    actions={
                        <button
                            onClick={actions.resetFilters}
                            className="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            {config.emptySearchState.resetLabel || trans('components.datatable.reset_filters_default')}
                        </button>
                    }
                />
            );
        }

        if (showEmptyState && config.emptyState) {
            return (
                <EmptyState
                    title={config.emptyState.title}
                    subtitle={config.emptyState.subtitle}
                    icon={config.emptyState.icon}
                    actions={config.emptyState.actions}
                />
            );
        }

        return null;
    };

    const renderLoadingOverlay = () => {
        return null;
    };

    return (
        <div className={`bg-white rounded-lg overflow-hidden ${className}`}>
            {(config.filters?.length || config.searchPlaceholder) && (
                <DataTableFilters
                    filters={config.filters || []}
                    values={state.filters}
                    searchValue={state.search}
                    searchPlaceholder={config.searchPlaceholder}
                    onSearchChange={actions.setSearch}
                    onFilterChange={actions.setFilter}
                    onReset={actions.resetFilters}
                    isLoading={isLoading || isNavigating}
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
                {renderLoadingOverlay()}

                {showEmptyState ? (
                    renderEmptyState()
                ) : (
                    <div className="overflow-x-auto custom-scrollbar">
                        <table className="min-w-full divide-y divide-gray-200">
                            {renderTableHeader()}
                            {renderTableBody()}
                        </table>
                    </div>
                )}
            </div>

            {!showEmptyState && (
                <DataTablePagination
                    data={data}
                    onPageChange={actions.goToPage}
                    onPerPageChange={actions.setPerPage}
                    isLoading={isLoading || isNavigating}
                    perPageOptions={config.perPageOptions}
                />
            )}
        </div>
    );
}