import { PageProps } from "@/types";
import { hasPermission } from "@/utils";
import { usePage, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useBulkActions, useConfirmationModal } from "../shared";

const useListGroups = () => {
    const { auth } = usePage<PageProps>().props;

    const canCreateGroups = hasPermission(auth.permissions, 'create groups');
    const canViewGroups = hasPermission(auth.permissions, 'view groups');
    const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
    const canToggleStatus = hasPermission(auth.permissions, 'update groups');

    const { selectedIds, setSelectedIds, isLoading, executeBulkAction } = useBulkActions();
    const bulkModal = useConfirmationModal<'activate' | 'deactivate'>();

    const handleCreateGroup = () => {
        router.visit(route('groups.create'));
    };

    const handleViewGroup = (groupId: number) => {
        router.visit(route('groups.show', { group: groupId }));
    };

    const handleEditGroup = (groupId: number) => {
        router.visit(route('groups.edit', { group: groupId }));
    };

    const handleBulkActivate = (ids: (number | string)[]) => {
        setSelectedIds(ids);
        bulkModal.openModal('activate');
    };

    const handleBulkDeactivate = (ids: (number | string)[]) => {
        setSelectedIds(ids);
        bulkModal.openModal('deactivate');
    };

    const handleConfirmAction = () => {
        if (!bulkModal.data) return;

        const routeName = bulkModal.data === 'activate'
            ? route('groups.bulk-activate')
            : route('groups.bulk-deactivate');

        executeBulkAction(routeName, { ids: selectedIds }, {
            onSuccess: () => bulkModal.closeModal(),
            onError: () => bulkModal.closeModal()
        });
    };


    return {
        selectedIds,
        isLoading,
        bulkModal,
        canCreateGroups,
        canViewGroups,
        canUpdateGroups,
        canToggleStatus,
        handleCreateGroup,
        handleViewGroup,
        handleEditGroup,
        handleBulkActivate,
        handleBulkDeactivate,
        handleConfirmAction
    };
};

export default useListGroups;