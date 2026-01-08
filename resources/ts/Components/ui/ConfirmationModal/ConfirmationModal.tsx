import React from 'react';
import Modal, { ModalSize } from '../Modal/Modal';
import Button from '../Button/Button';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

type ModalType = 'danger' | 'warning' | 'info';

interface ConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText: string;
    cancelText: string;
    type?: ModalType;
    icon?: React.ComponentType<React.SVGProps<SVGSVGElement>>;
    loading?: boolean;
    isCloseableInside?: boolean;
    children?: React.ReactNode;
    size?: ModalSize;
    testIdModal?: string;
    testIdConfirmButton?: string;
    testIdCancelButton?: string;
}

const getTypeStyles = (type: ModalType) => {
    switch (type) {
        case 'danger':
            return {
                iconColor: 'text-red-600',
                iconBg: 'bg-red-100',
                confirmButton: 'danger'
            };
        case 'warning':
            return {
                iconColor: 'text-yellow-600',
                iconBg: 'bg-yellow-100',
                confirmButton: 'warning'
            };
        case 'info':
            return {
                iconColor: 'text-blue-600',
                iconBg: 'bg-blue-100',
                confirmButton: 'primary'
            };
        default:
            return {
                iconColor: 'text-yellow-600',
                iconBg: 'bg-yellow-100',
                confirmButton: 'warning'
            };
    }
};


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
    children,
    testIdModal = "confirmation-modal",
    testIdConfirmButton = "confirm-button",
    testIdCancelButton = "cancel-button"
}) => {


    const styles = getTypeStyles(type);

    return (
        <Modal isOpen={isOpen} size={size} onClose={onClose} isCloseableInside={isCloseableInside && !loading}
            testIdModal={testIdModal}
        >
            <div className=' min-h-72 flex flex-col items-center justify-between p-6'>
                {React.createElement(icon, { className: `w-12 h-12 mb-4 ${styles.iconColor}` })}
                <h3 className="text-lg font-bold mb-4">{title}</h3>
                <p className="text-gray-600 mb-6 text-center ">
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
                        data-e2e={testIdCancelButton}
                    >
                        {cancelText}
                    </Button>
                    <Button
                        size="md"
                        color={styles.confirmButton as any}
                        onClick={onConfirm}
                        loading={loading}
                        disabled={loading}
                        data-e2e={testIdConfirmButton}
                    >
                        {confirmText}
                    </Button>
                </div>
            </div>
        </Modal>
    );
};

export default ConfirmationModal;