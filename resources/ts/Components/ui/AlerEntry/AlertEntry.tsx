import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from '@heroicons/react/24/outline';
import React from 'react';

type AlertType = 'success' | 'error' | 'warning' | 'info';

interface AlertEntryProps {
    title: string;
    children?: React.ReactNode;
    className?: string;
    type: AlertType;
}

const ALERT_STYLES: Record<
    AlertType,
    { container: string; icon: string; title: string; body: string }
> = {
    success: {
        container: 'bg-green-50 border-green-200 ring-1 ring-green-100',
        icon: 'text-green-500',
        title: 'text-green-800',
        body: 'text-green-700',
    },
    error: {
        container: 'bg-red-50 border-red-200 ring-1 ring-red-100',
        icon: 'text-red-500',
        title: 'text-red-800',
        body: 'text-red-700',
    },
    warning: {
        container: 'bg-amber-50 border-amber-200 ring-1 ring-amber-100',
        icon: 'text-amber-500',
        title: 'text-amber-800',
        body: 'text-amber-700',
    },
    info: {
        container: 'bg-blue-50 border-blue-200 ring-1 ring-blue-100',
        icon: 'text-blue-500',
        title: 'text-blue-800',
        body: 'text-blue-700',
    },
};

const ALERT_ICONS: Record<AlertType, React.ElementType> = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

/**
 * Alert notification component for displaying contextual feedback messages.
 *
 * Supports success, error, warning, and info types with distinct visual styles.
 */
const AlertEntry: React.FC<AlertEntryProps> = ({ title, children, type, className }) => {
    const styles = ALERT_STYLES[type];
    const Icon = ALERT_ICONS[type];

    return (
        <div
            className={`rounded-lg border p-4 ${styles.container} ${className || ''}`}
            role="alert"
        >
            <div className="flex gap-3">
                <Icon className={`h-5 w-5 shrink-0 mt-0.5 ${styles.icon}`} aria-hidden="true" />
                <div className="flex-1 min-w-0">
                    <h4 className={`text-sm font-semibold leading-6 ${styles.title}`}>{title}</h4>
                    {children && (
                        <div className={`mt-1 text-sm leading-6 ${styles.body}`}>{children}</div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AlertEntry;
