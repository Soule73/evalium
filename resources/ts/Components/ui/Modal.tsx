export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';

interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    children: React.ReactNode;
    size?: ModalSize;
    className?: string;
    isCloseableInside?: boolean;
}

const Modal: React.FC<ModalProps> = ({ isOpen, onClose, children, size = 'md', className, isCloseableInside = true }) => {
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
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="absolute inset-0 bg-black opacity-50" onClick={isCloseableInside ? onClose : undefined} />
            <div className={`bg-white rounded-lg shadow-lg z-10 p-6 ${sizeClasses[size]} ${className}`}>
                {children}
            </div>
        </div>
    );
};

export default Modal;