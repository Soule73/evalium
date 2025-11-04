import React, { useEffect, useState } from 'react';
import { CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { trans } from '@/utils/translations';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface ToastProps {
    id: string;
    type: ToastType;
    title?: string;
    message: string;
    autoClose?: boolean;
    duration?: number;
    onClose: (id: string) => void;
}

const toastConfig = {
    success: {
        icon: CheckCircleIcon,
        bgColor: 'bg-green-50',
        borderColor: 'border-green-200',
        iconColor: 'text-green-400',
        titleColor: 'text-green-800',
        messageColor: 'text-green-700',
        closeButtonColor: 'text-green-500 hover:bg-green-100 focus:ring-green-600'
    },
    error: {
        icon: XCircleIcon,
        bgColor: 'bg-red-50',
        borderColor: 'border-red-200',
        iconColor: 'text-red-400',
        titleColor: 'text-red-800',
        messageColor: 'text-red-700',
        closeButtonColor: 'text-red-500 hover:bg-red-100 focus:ring-red-600'
    },
    warning: {
        icon: ExclamationCircleIcon,
        bgColor: 'bg-yellow-50',
        borderColor: 'border-yellow-200',
        iconColor: 'text-yellow-400',
        titleColor: 'text-yellow-800',
        messageColor: 'text-yellow-700',
        closeButtonColor: 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600'
    },
    info: {
        icon: InformationCircleIcon,
        bgColor: 'bg-blue-50',
        borderColor: 'border-blue-200',
        iconColor: 'text-blue-400',
        titleColor: 'text-blue-800',
        messageColor: 'text-blue-700',
        closeButtonColor: 'text-blue-500 hover:bg-blue-100 focus:ring-blue-600'
    }
};

const Toast: React.FC<ToastProps> = ({
    id,
    type,
    title,
    message,
    autoClose = true,
    duration = 5000,
    onClose
}) => {
    const [isVisible, setIsVisible] = useState(false);
    const [isLeaving, setIsLeaving] = useState(false);

    const config = toastConfig[type];
    const IconComponent = config.icon;

    useEffect(() => {
        const timer = setTimeout(() => setIsVisible(true), 10);
        return () => clearTimeout(timer);
    }, []);

    useEffect(() => {
        if (autoClose && duration > 0) {
            const timer = setTimeout(() => {
                handleClose();
            }, duration);
            return () => clearTimeout(timer);
        }
    }, [autoClose, duration, id]);

    const handleClose = () => {
        setIsLeaving(true);
        setTimeout(() => {
            onClose(id);
        }, 300);
    };

    return (
        <div
            className={`
                w-full ${config.bgColor} ${config.borderColor} border rounded-lg shadow-lg
                transform transition-all duration-300 ease-in-out overflow-hidden
                ${isVisible && !isLeaving
                    ? 'translate-x-0 opacity-100'
                    : 'translate-x-full opacity-0'
                }
            `}
        >
            <div className="p-4 w-full overflow-hidden">
                <div className="flex items-start w-full">
                    <div className="shrink-0">
                        <IconComponent
                            className={`h-6 w-6 ${config.iconColor}`}
                            aria-hidden="true"
                        />
                    </div>

                    <div className="ml-3 flex-1 min-w-0 overflow-hidden">
                        {title && (
                            <p className={`text-sm font-medium ${config.titleColor} wrap-break-word word-break`}>
                                {title}
                            </p>
                        )}
                        <p className={`text-sm ${config.messageColor} ${title ? 'mt-1' : ''} wrap-break-wordwordword word-break leading-relaxed`}>
                            {message}
                        </p>
                    </div>

                    <div className="ml-4 shrink-0 flex">
                        <button
                            type="button"
                            className={`
                                rounded-md inline-flex ${config.closeButtonColor}
                                focus:outline-none focus:ring-2 focus:ring-offset-2
                                transition-colors duration-200 p-1
                            `}
                            onClick={handleClose}
                        >
                            <span className="sr-only">{trans('components.toast.close')}</span>
                            <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>

            {autoClose && duration > 0 && (
                <div className="w-full bg-gray-200 rounded-b-lg h-1 overflow-hidden">
                    <div
                        className={`h-full bg-current ${config.iconColor} origin-left`}
                        style={{
                            animation: `shrink ${duration}ms linear forwards`
                        }}
                    />
                </div>
            )}
        </div>
    );
};

export default Toast;