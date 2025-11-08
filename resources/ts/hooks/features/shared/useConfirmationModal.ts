import { useState, useCallback } from 'react';

interface ConfirmationModalState<T = unknown> {
    isOpen: boolean;
    data: T | null;
}

export function useConfirmationModal<T = unknown>(initialData: T | null = null) {
    const [modal, setModal] = useState<ConfirmationModalState<T>>({
        isOpen: false,
        data: initialData
    });

    const openModal = useCallback((data: T) => {
        setModal({ isOpen: true, data });
    }, []);

    const closeModal = useCallback(() => {
        setModal({ isOpen: false, data: null });
    }, []);

    return {
        isOpen: modal.isOpen,
        data: modal.data,
        openModal,
        closeModal
    };
}
