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
    if (!isOpen) return null;

    const sizeClasses = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        full: 'w-full h-full',
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center" data-e2e={testIdModal}>
            <div
                className="absolute inset-0 bg-black opacity-50"
                onClick={isCloseableInside ? onClose : undefined}
            />
            <div
                className={`bg-white rounded-lg shadow-lg z-10 p-6 ${sizeClasses[size]} ${className}`}
            >
                {title && (
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                        {isCloseableInside && (
                            <button
                                onClick={onClose}
                                className="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <XMarkIcon className="w-5 h-5" />
                            </button>
                        )}
                    </div>
                )}
                {children}
            </div>
        </div>
    );
};

export default Modal;
