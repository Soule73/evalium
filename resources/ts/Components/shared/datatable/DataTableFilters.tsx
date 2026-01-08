import React from 'react';
import { Select } from '@/Components';
import { Input } from '@examena/ui';
import { FunnelIcon } from '@heroicons/react/24/outline';
import { FilterConfig } from '@/types/datatable';
import { trans } from '@/utils';

interface DataTableFiltersProps {
    filters: FilterConfig[];
    values: Record<string, string>;
    searchValue: string;
    searchPlaceholder?: string;
    onSearchChange: (value: string) => void;
    onFilterChange: (key: string, value: string) => void;
    onReset: () => void;
    showResetButton?: boolean;
    isLoading?: boolean;
    dataTableSearchInputId?: string;
    testIdResetFiltersButton?: string;

}

export const DataTableFilters: React.FC<DataTableFiltersProps> = ({
    filters,
    values,
    searchValue,
    searchPlaceholder,
    onSearchChange,
    onFilterChange,
    onReset,
    showResetButton = true,
    isLoading = false,
    dataTableSearchInputId = 'datatable-search-input',
    testIdResetFiltersButton = 'datatable-reset-filters-button'
}) => {
    const hasActiveFilters = searchValue || Object.values(values).some(v => v);

    return (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-gray-50 border-b border-gray-200">
            <div className="flex items-center gap-3">
                <div className="flex-1 max-w-sm">
                    <Input
                        type='search'
                        placeholder={searchPlaceholder ?? trans('admin_pages.common.search_placeholder')}
                        value={searchValue}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onSearchChange(e.target.value)}
                        className="py-2! px-3! text-sm"
                        id={dataTableSearchInputId}
                    />
                </div>

                {isLoading && (
                    <div className="flex items-center gap-2 text-blue-600" data-e2e="datatable-loading-indicator">
                        <div className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <span className="text-sm">{trans('admin_pages.common.loading')}</span>
                    </div>
                )}
            </div>

            <div className="flex items-center gap-3">
                {filters.map((filter) => (
                    <div key={filter.key} className="relative">
                        {filter.type === 'select' && filter.options ? (
                            <Select
                                id={`datatable-filter-${filter.key}`}
                                options={filter.options}
                                value={values[filter.key] || filter.defaultValue || ''}
                                onChange={(value) => onFilterChange(filter.key, String(value))}
                                placeholder={filter.label}
                                className="min-w-37.5 text-sm"
                                searchable={filter.options.length > 5}
                            />
                        ) : (
                            <Input
                                type="text"
                                placeholder={filter.placeholder || filter.label}
                                value={values[filter.key] || ''}
                                onChange={(e) => onFilterChange(filter.key, e.target.value)}
                                className="py-2! px-3! text-sm max-w-32"
                                id={`datatable-filter-${filter.key}`}
                            />
                        )}
                    </div>
                ))}

                {showResetButton && hasActiveFilters && (
                    <button
                        onClick={onReset}
                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        data-e2e={testIdResetFiltersButton}
                    >
                        <FunnelIcon className="w-4 h-4 mr-1" />
                        {trans('admin_pages.common.reset')}
                    </button>
                )}
            </div>
        </div>
    );
};