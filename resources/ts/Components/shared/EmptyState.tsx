import React from 'react';
import { DocumentTextIcon } from '@heroicons/react/24/outline';

interface EmptyStateProps {
    title: string;
    subtitle: string;
    icon?: React.ReactNode;
    actions?: React.ReactNode;
    className?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
    title,
    subtitle,
    icon,
    actions,
    className = ''
}) => {
    return (
        <div className={`text-center py-12 bg-white ${className}`}>
            <div className="text-gray-400">
                {icon || <DocumentTextIcon className="w-12 h-12 mx-auto mb-4" />}
            </div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">
                {title}
            </h3>
            <p className="text-gray-600 mb-6 text-sm">
                {subtitle}
            </p>
            {actions && <div className="inline-block">{actions}</div>}
        </div>
    );
};