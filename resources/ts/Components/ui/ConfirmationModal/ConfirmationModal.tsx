import React from 'react';
import Modal, { ModalSize } from '../Modal/Modal';
import Button from '../Button/Button';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface ConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText: string;
    cancelText: string;
    type?: 'danger' | 'warning' | 'info';
    icon?: React.ComponentType<React.SVGProps<SVGSVGElement>>;
    loading?: boolean;
    isCloseableInside?: boolean;
    children?: React.ReactNode;
    size?: ModalSize;
}

const ConfirmationModal: React.FC<ConfirmationModalProps> = ({
    isOpen,
    onClose,
    onConfirm,
    title,
    message,
    confirmText,
    cancelText,
    type = 'warning',
    icon = ExclamationTriangleIcon,
    loading = false,
    isCloseableInside = true,
    size = "md",
    children
}) => {
    // const defaultConfirmText = trans('components.confirmation_modal.confirm');
    // const defaultCancelText = trans('components.confirmation_modal.cancel');

    const getTypeStyles = () => {
        switch (type) {
            case 'danger':
                return {
                    iconColor: 'text-red-600 dark:text-[--color-dark-danger]',
                    iconBg: 'bg-red-100 dark:bg-[--color-dark-danger]/20',
                    confirmButton: 'danger'
                };
            case 'warning':
                return {
                    iconColor: 'text-yellow-600 dark:text-[--color-dark-warning]',
                    iconBg: 'bg-yellow-100 dark:bg-[--color-dark-warning]/20',
                    confirmButton: 'warning'
                };
            case 'info':
                return {
                    iconColor: 'text-blue-600 dark:text-[--color-dark-primary]',
                    iconBg: 'bg-blue-100 dark:bg-[--color-dark-primary]/20',
                    confirmButton: 'primary'
                };
            default:
                return {
                    iconColor: 'text-yellow-600 dark:text-[--color-dark-warning]',
                    iconBg: 'bg-yellow-100 dark:bg-[--color-dark-warning]/20',
                    confirmButton: 'warning'
                };
        }
    };

    const styles = getTypeStyles();

    return (
        <Modal isOpen={isOpen} size={size} onClose={onClose} isCloseableInside={isCloseableInside && !loading}
        >
            <div className=' min-h-72 flex flex-col items-center justify-between p-6'>
                {React.createElement(icon, { className: `w-12 h-12 mb-4 ${styles.iconColor}` })}
                <h3 className="text-lg font-bold mb-4 dark:text-[--color-dark-text]">{title}</h3>
                <p className="text-gray-600 dark:text-[--color-dark-text-secondary] mb-6 text-center ">
                    {message}
                </p>
                {children}
                <div className="flex justify-end w-full space-x-4">
                    <Button
                        size="md"
                        color="secondary"
                        variant="outline"
                        onClick={onClose}
                        disabled={loading}
                    >
                        {cancelText}
                    </Button>
                    <Button
                        size="md"
                        color={styles.confirmButton as any}
                        onClick={onConfirm}
                        loading={loading}
                        disabled={loading}
                    >
                        {confirmText}
                    </Button>
                </div>
            </div>
        </Modal>
    );
};

export default ConfirmationModal;