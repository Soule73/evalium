import { PageProps } from "@/types";
import { hasPermission } from "@/utils";
import { usePage, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useConfirmationModal } from "../shared";

const useListLevels = () => {
    const { auth } = usePage<PageProps>().props;

    const canCreateLevels = hasPermission(auth.permissions, 'create levels');
    const canUpdateLevels = hasPermission(auth.permissions, 'update levels');
    const canDeleteLevels = hasPermission(auth.permissions, 'delete levels');

    const deleteModal = useConfirmationModal<{ id: number; name: string }>();

    const handleCreate = () => {
        router.visit(route('levels.create'));
    };

    const handleEdit = (levelId: number) => {
        router.visit(route('levels.edit', { level: levelId }));
    };

    const handleToggleStatus = (levelId: number) => {
        router.patch(route('levels.toggle-status', { level: levelId }), {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (levelId: number) => {
        if (!deleteModal.data) return;
        router.delete(route('levels.destroy', { level: levelId }), {
            onFinish: () => deleteModal.closeModal()
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
        handleDelete
    };
};

export default useListLevels;