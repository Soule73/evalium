import React from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { trans } from '@/utils';

interface BulkActionsProps {
    selectedCount: number;
    onDeselectAll: () => void;
    children: React.ReactNode;
}

export function BulkActions({ selectedCount, onDeselectAll, children }: BulkActionsProps) {
    if (selectedCount === 0) return null;

    const itemsText = trans('components.datatable.items_selected', { count: selectedCount });
    const suffixText = trans('components.datatable.items_selected_suffix', { count: selectedCount });

    return (
        <div className="bg-blue-50 border-b border-blue-200 px-6 py-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <div className="flex items-center space-x-2">
                        <span className="text-sm font-medium text-blue-900">
                            {itemsText} {suffixText}
                        </span>
                        <button
                            onClick={onDeselectAll}
                            className="text-blue-600 hover:text-blue-800 transition-colors"
                            aria-label={trans('components.datatable.deselect_all')}
                        >
                            <XMarkIcon className="h-5 w-5" />
                        </button>
                    </div>
                    {children && (
                        <>
                            <div className="h-6 w-px bg-blue-300" />
                            <div className="flex items-center space-x-2">
                                {children}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
