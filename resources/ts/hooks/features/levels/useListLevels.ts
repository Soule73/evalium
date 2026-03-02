import { useState, useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { type Level } from '@evalium/utils/types';
import { useConfirmationModal } from '@/hooks/features/shared/useConfirmationModal';

export const useListLevels = () => {
    const { auth } = usePage<{ auth: { permissions: string[] } }>().props;
    const deleteModal = useConfirmationModal<{ id: number; name: string }>();

    const [formModal, setFormModal] = useState<{ isOpen: boolean; level: Level | null }>({
        isOpen: false,
        level: null,
    });

    const canCreateLevels = auth.permissions?.includes('create levels') || false;
    const canUpdateLevels = auth.permissions?.includes('update levels') || false;
    const canDeleteLevels = auth.permissions?.includes('delete levels') || false;

    const handleCreate = useCallback(() => {
        setFormModal({ isOpen: true, level: null });
    }, []);

    const handleEdit = useCallback((level: Level) => {
        setFormModal({ isOpen: true, level });
    }, []);

    const closeFormModal = useCallback(() => {
        setFormModal({ isOpen: false, level: null });
    }, []);

    const handleToggleStatus = (level: Level) => {
        router.post(
            route('admin.levels.toggle-status', level.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleDelete = (id: number) => {
        router.delete(route('admin.levels.destroy', id), {
            onSuccess: () => deleteModal.closeModal(),
        });
    };

    return {
        canCreateLevels,
        canUpdateLevels,
        canDeleteLevels,
        deleteModal,
        formModal,
        handleCreate,
        handleEdit,
        closeFormModal,
        handleToggleStatus,
        handleDelete,
    };
};
