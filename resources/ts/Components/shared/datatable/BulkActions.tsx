import React from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface BulkActionsProps {
    selectedCount: number;
    onDeselectAll: () => void;
    children: React.ReactNode;
}

export function BulkActions({ selectedCount, onDeselectAll, children }: BulkActionsProps) {
    const { t } = useTranslations();
    if (selectedCount === 0) return null;

    const itemsText = t('components.datatable.items_selected', { count: selectedCount });
    const suffixText = t('components.datatable.items_selected_suffix', { count: selectedCount });

    return (
        <div className="bg-indigo-50 border-b border-indigo-200 px-6 py-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <div className="flex items-center space-x-2">
                        <span className="text-sm font-medium text-indigo-900">
                            {itemsText} {suffixText}
                        </span>
                        <button
                            onClick={onDeselectAll}
                            className="text-indigo-600 hover:text-indigo-800 transition-colors"
                            aria-label={t('components.datatable.deselect_all')}
                        >
                            <XMarkIcon className="h-5 w-5" />
                        </button>
                    </div>
                    {children && (
                        <>
                            <div className="h-6 w-px bg-indigo-300" />
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
