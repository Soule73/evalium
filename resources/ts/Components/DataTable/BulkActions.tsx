import React from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';

interface BulkActionsProps {
    selectedCount: number;
    onDeselectAll: () => void;
    children: React.ReactNode;
}

export function BulkActions({ selectedCount, onDeselectAll, children }: BulkActionsProps) {
    if (selectedCount === 0) return null;

    return (
        <div className="bg-blue-50 border-b border-blue-200 px-6 py-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <div className="flex items-center space-x-2">
                        <span className="text-sm font-medium text-blue-900">
                            {selectedCount} élément{selectedCount > 1 ? 's' : ''} sélectionné{selectedCount > 1 ? 's' : ''}
                        </span>
                        <button
                            onClick={onDeselectAll}
                            className="text-blue-600 hover:text-blue-800 transition-colors"
                            aria-label="Désélectionner tout"
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
