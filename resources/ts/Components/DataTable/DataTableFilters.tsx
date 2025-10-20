import React from 'react';
import { Input } from '@/Components';
import { FunnelIcon } from '@heroicons/react/24/outline';
import { FilterConfig } from '@/types/datatable';
import Select from '../Select';

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
}

export const DataTableFilters: React.FC<DataTableFiltersProps> = ({
    filters,
    values,
    searchValue,
    searchPlaceholder = "Rechercher...",
    onSearchChange,
    onFilterChange,
    onReset,
    showResetButton = true,
    isLoading = false
}) => {
    const hasActiveFilters = searchValue || Object.values(values).some(v => v);

    return (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-gray-50 border-b border-gray-200">
            <div className="flex items-center gap-3">
                <div className="flex-1 max-w-sm">
                    <Input
                        type="text"
                        placeholder={searchPlaceholder}
                        value={searchValue}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onSearchChange(e.target.value)}
                        className="!py-2 !px-3 text-sm"
                    />
                </div>

                {isLoading && (
                    <div className="flex items-center gap-2 text-blue-600">
                        <div className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <span className="text-sm">Chargement...</span>
                    </div>
                )}
            </div>

            <div className="flex items-center gap-3">
                {filters.map((filter) => (
                    <div key={filter.key} className="relative">
                        {filter.type === 'select' && filter.options ? (
                            <Select
                                options={filter.options}
                                value={values[filter.key] || filter.defaultValue || ''}
                                onChange={(value) => onFilterChange(filter.key, String(value))}
                                placeholder={filter.label}
                                className="min-w-[150px] text-sm"
                                searchable={filter.options.length > 5}
                            />
                        ) : (
                            <Input
                                type="text"
                                placeholder={filter.placeholder || filter.label}
                                value={values[filter.key] || ''}
                                onChange={(e) => onFilterChange(filter.key, e.target.value)}
                                className="!py-2 !px-3 text-sm max-w-32"
                            />
                        )}
                    </div>
                ))}

                {showResetButton && hasActiveFilters && (
                    <button
                        onClick={onReset}
                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                    >
                        <FunnelIcon className="w-4 h-4 mr-1" />
                        Réinitialiser
                    </button>
                )}
            </div>
        </div>
    );
};