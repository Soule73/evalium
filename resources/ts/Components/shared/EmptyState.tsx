import React from 'react';
import { DocumentTextIcon } from '@heroicons/react/24/outline';

interface EmptyStateProps extends React.HTMLAttributes<HTMLDivElement> {
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
    className = '',
    ...props
}) => {
    return (
        <div className={`text-center py-12 bg-white ${className}`} {...props}>
            <div className="flex justify-center text-gray-400 mb-4">
                {icon || <DocumentTextIcon className="w-12 h-12" />}
            </div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">{title}</h3>
            <p className="text-gray-600 mb-6 text-sm">{subtitle}</p>
            {actions && <div className="flex justify-center">{actions}</div>}
        </div>
    );
};
