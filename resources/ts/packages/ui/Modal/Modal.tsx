import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { XMarkIcon } from '@heroicons/react/24/outline';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';

interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    children: React.ReactNode;
    title?: string;
    size?: ModalSize;
    className?: string;
    isCloseableInside?: boolean;
    testIdModal?: string;
}

const sizeClasses: Record<ModalSize, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    full: 'w-[calc(100%-2rem)] h-[calc(100vh-2rem)]',
};

/**
 * Accessible modal dialog with enter/exit animations, scroll lock and Escape key support.
 *
 * Renders via React Portal to the document body.
 */
const Modal: React.FC<ModalProps> = ({
    isOpen,
    onClose,
    children,
    title,
    size = 'md',
    className,
    isCloseableInside = true,
    testIdModal = 'confirmation-modal',
}) => {
    const [visible, setVisible] = useState(false);
    const [animating, setAnimating] = useState(false);
    const dialogRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (isOpen) {
            setVisible(true);
            requestAnimationFrame(() => setAnimating(true));
        } else {
            setAnimating(false);
            const timer = setTimeout(() => setVisible(false), 200);
            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    useEffect(() => {
        if (!visible) return;
        const original = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = original;
        };
    }, [visible]);

    const handleKeyDown = useCallback(
        (e: KeyboardEvent) => {
            if (e.key === 'Escape' && isCloseableInside) {
                onClose();
            }
        },
        [isCloseableInside, onClose],
    );

    useEffect(() => {
        if (!visible) return;
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [visible, handleKeyDown]);

    useEffect(() => {
        if (visible && dialogRef.current) {
            dialogRef.current.focus();
        }
    }, [visible]);

    if (!visible) return null;

    const isFullSize = size === 'full';

    const modal = (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby={title ? 'modal-title' : undefined}
            data-e2e={testIdModal}
        >
            <div
                className={`absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-200 ${
                    animating ? 'opacity-100' : 'opacity-0'
                }`}
                onClick={isCloseableInside ? onClose : undefined}
                aria-hidden="true"
            />

            <div
                ref={dialogRef}
                tabIndex={-1}
                className={[
                    'relative z-10 w-full bg-white rounded-xl shadow-2xl outline-none',
                    'transition-all duration-200 ease-out',
                    animating
                        ? 'opacity-100 translate-y-0 scale-100'
                        : 'opacity-0 translate-y-4 scale-95',
                    isFullSize ? 'flex flex-col' : 'max-h-[calc(100vh-2rem)]',
                    sizeClasses[size],
                    className,
                ]
                    .filter(Boolean)
                    .join(' ')}
            >
                {title && (
                    <div className="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                        <h3
                            id="modal-title"
                            className="text-lg font-semibold text-gray-900 truncate pr-4"
                        >
                            {title}
                        </h3>
                        {isCloseableInside && (
                            <button
                                type="button"
                                onClick={onClose}
                                className="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors shrink-0"
                                aria-label="Close"
                            >
                                <XMarkIcon className="w-5 h-5" />
                            </button>
                        )}
                    </div>
                )}

                {isFullSize ? (
                    <div className="flex-1 min-h-0 flex flex-col overflow-y-auto p-6">
                        {children}
                    </div>
                ) : (
                    <div className="overflow-y-auto p-6">{children}</div>
                )}
            </div>
        </div>
    );

    return createPortal(modal, document.body);
};

export default Modal;
