import React from 'react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';
import { PaginationType } from '@/types/datatable';
import { trans } from '@/utils';

interface DataTablePaginationProps<T> {
    data: PaginationType<T>;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
    isLoading?: boolean;
    perPageOptions?: number[];
}

export function DataTablePagination<T>({
    data,
    onPageChange,
    onPerPageChange,
    isLoading = false,
    perPageOptions = [10, 25, 50, 100]
}: DataTablePaginationProps<T>) {
    const {
        current_page,
        last_page,
        from,
        to,
        total,
        per_page
    } = data;

    const getVisiblePages = () => {
        const delta = 2;
        const pages: (number | string)[] = [];

        if (current_page > delta + 1) {
            pages.push(1);
            if (current_page > delta + 2) {
                pages.push('...');
            }
        }

        for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
            pages.push(i);
        }

        if (current_page < last_page - delta) {
            if (current_page < last_page - delta - 1) {
                pages.push('...');
            }
            pages.push(last_page);
        }

        return pages;
    };

    const visiblePages = getVisiblePages();

    const handlePageChange = (page: number) => {
        if (page !== current_page && page >= 1 && page <= last_page && !isLoading) {
            onPageChange(page);
        }
    };

    const handlePerPageChange = (newPerPage: number) => {
        if (newPerPage !== per_page && !isLoading) {
            onPerPageChange(newPerPage);
        }
    };

    return (
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-4 bg-white border-t border-gray-200">
            <div className="flex items-center gap-4">
                <div className="text-sm text-gray-700">
                    {trans('components.datatable.showing_records', {
                        from: String(from || 0),
                        to: String(to || 0),
                        total: String(total)
                    })}
                </div>

                <div className="flex items-center gap-2">
                    <label htmlFor="per-page" className="text-sm text-gray-700">
                        {trans('components.datatable.per_page')}
                    </label>
                    <select
                        id="per-page"
                        value={per_page}
                        onChange={(e) => handlePerPageChange(Number(e.target.value))}
                        disabled={isLoading}
                        className="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        {perPageOptions.map((option) => (
                            <option key={option} value={option}>
                                {option}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="flex items-center gap-1">
                <button
                    onClick={() => handlePageChange(current_page - 1)}
                    disabled={current_page <= 1 || isLoading}
                    className="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ChevronLeftIcon className="h-4 w-4" />
                    <span className="sr-only">{trans('components.datatable.previous')}</span>
                </button>

                {visiblePages.map((page, index) => (
                    <React.Fragment key={index}>
                        {page === '...' ? (
                            <span className="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">
                                ...
                            </span>
                        ) : (
                            <button
                                onClick={() => handlePageChange(page as number)}
                                disabled={isLoading}
                                className={`inline-flex items-center px-4 py-2 text-sm font-medium border ${page === current_page
                                    ? 'bg-blue-50 border-blue-500 text-blue-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    } disabled:opacity-50 disabled:cursor-not-allowed`}
                            >
                                {page}
                            </button>
                        )}
                    </React.Fragment>
                ))}

                <button
                    onClick={() => handlePageChange(current_page + 1)}
                    disabled={current_page >= last_page || isLoading}
                    className="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ChevronRightIcon className="h-4 w-4" />
                    <span className="sr-only">{trans('components.datatable.next')}</span>
                </button>
            </div>
        </div>
    );
}