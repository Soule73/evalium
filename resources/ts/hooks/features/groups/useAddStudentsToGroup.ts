import { Group, PageProps, User } from "@/types";
import { hasPermission } from "@/utils";
import { usePage, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useBulkActions, useConfirmationModal } from "../shared";
import { PaginationType } from "@/types/datatable";


const useAddStudentsToGroup = (
    group: Group & { active_students_count?: number },
    availableStudents: PaginationType<User>
) => {
    const { auth } = usePage<PageProps>().props;

    const canManageGroupStudents = hasPermission(auth.permissions, 'manage students');

    const { selectedIds, setSelectedIds, isLoading, executeBulkAction } = useBulkActions();
    const confirmModal = useConfirmationModal();

    const handleCancel = () => {
        router.visit(route('groups.show', { group: group.id }));
    };

    const handleAssignStudents = (_ids: (number | string)[]) => {
        confirmModal.openModal(null);
    };

    const handleConfirmAssign = () => {
        if (selectedIds.length === 0) return;

        executeBulkAction(
            route('groups.store-students', { group: group.id }),
            { student_ids: selectedIds },
            {
                onSuccess: () => {
                    confirmModal.closeModal();
                    router.visit(route('groups.show', { group: group.id }));
                },
                onError: () => {
                    confirmModal.closeModal();
                }
            }
        );
    };

    const availableSlots = group.max_students - (group.active_students_count || 0);
    const maxSelectable = Math.min(availableSlots, availableStudents.total);

    return {
        canManageGroupStudents,
        selectedIds,
        isLoading,
        handleCancel,
        handleAssignStudents,
        handleConfirmAssign,
        confirmModal,
        maxSelectable,
        availableSlots,
        setSelectedIds
    };

};

export default useAddStudentsToGroup;