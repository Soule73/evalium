import { router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { type Level } from '@/types';
import { useConfirmationModal } from '@/hooks/features/shared/useConfirmationModal';

export const useListLevels = () => {
    const { auth } = usePage<{ auth: { permissions: string[] } }>().props;
    const deleteModal = useConfirmationModal<{ id: number; name: string }>();

    const canCreateLevels = auth.permissions?.includes('create levels') || false;
    const canUpdateLevels = auth.permissions?.includes('update levels') || false;
    const canDeleteLevels = auth.permissions?.includes('delete levels') || false;

    const handleCreate = () => {
        router.visit(route('admin.levels.create'));
    };

    const handleEdit = (level: Level) => {
        router.visit(route('admin.levels.edit', level.id));
    };

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
        handleCreate,
        handleEdit,
        handleToggleStatus,
        handleDelete,
    };
};
