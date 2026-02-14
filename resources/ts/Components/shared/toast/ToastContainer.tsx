import React from 'react';
import { createPortal } from 'react-dom';
import { useToast, type ToastPosition } from './ToastContext';
import { Toast } from './Toast';

const positionClasses: Record<ToastPosition, string> = {
    'top-right': 'top-4 right-4 items-end',
    'top-left': 'top-4 left-4 items-start',
    'top-center': 'top-4 left-1/2 transform -translate-x-1/2 items-center',
    'bottom-right': 'bottom-4 right-4 items-end',
    'bottom-left': 'bottom-4 left-4 items-start',
    'bottom-center': 'bottom-4 left-1/2 transform -translate-x-1/2 items-center'
};

interface ToastContainerProps {
    position?: ToastPosition;
}

const ToastContainer: React.FC<ToastContainerProps> = ({ position }) => {
    const { toasts, removeToast } = useToast();

    const toastsByPosition = toasts.reduce((acc, toast) => {
        const toastPosition = toast.position || position || 'top-right';
        if (!acc[toastPosition]) {
            acc[toastPosition] = [];
        }
        acc[toastPosition].push(toast);
        return acc;
    }, {} as Record<ToastPosition, typeof toasts>);

    if (toasts.length === 0) {
        return null;
    }

    return createPortal(
        <>
            {Object.entries(toastsByPosition).map(([pos, positionToasts]) => {
                const positionKey = pos as ToastPosition;
                const positionClass = positionClasses[positionKey];

                return (
                    <div
                        key={positionKey}
                        className={`
                            fixed z-50 pointer-events-none toast-container
                            flex flex-col space-y-4 
                            ${positionClass}
                        `}
                        style={{
                            maxHeight: 'calc(100vh - 2rem)',
                            width: 'min(400px, calc(100vw - 2rem))',
                        }}
                    >
                        <div className="flex flex-col space-y-4 overflow-hidden">
                            {positionToasts.map((toast) => (
                                <div
                                    key={toast.id}
                                    className="pointer-events-auto w-full"
                                >
                                    <Toast
                                        id={toast.id}
                                        type={toast.type}
                                        title={toast.title}
                                        message={toast.message}
                                        autoClose={toast.autoClose}
                                        duration={toast.duration}
                                        onClose={removeToast}
                                        closeLabel={toast.closeLabel}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                );
            })}
        </>,
        document.body
    );
};

export { ToastContainer };