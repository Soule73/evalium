import { Group, PageProps, User } from "@/types";
import { hasPermission } from "@/utils";
import { usePage, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useCallback } from "react";
import { useConfirmationModal, useBulkActions } from "../shared";

const useShowGroup = (
    group: Group
) => {

    const { auth } = usePage<PageProps>().props;

    const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
    const canDeleteGroups = hasPermission(auth.permissions, 'delete groups');
    const canManageGroupStudents = hasPermission(auth.permissions, 'manage students');

    const deleteGroupModal = useConfirmationModal();
    const removeStudentModal = useConfirmationModal<User>();
    const { selectedIds, setSelectedIds, isLoading, executeBulkAction } = useBulkActions();
    const bulkRemoveModal = useConfirmationModal();

    const handleEditGroup = useCallback(() => {
        router.visit(route('groups.edit', { group: group.id }));
    }, [group.id]);

    const handleAssignStudents = useCallback(() => {
        router.visit(route('groups.assign-students', { group: group.id }));
    }, [group.id]);

    const handleDeleteGroup = useCallback(() => {
        router.delete(route('groups.destroy', { group: group.id }), {
            onSuccess: () => {
                router.visit(route('groups.index'));
            }
        });
    }, [group.id]);

    const handleRemoveStudent = useCallback((student: User) => {
        removeStudentModal.openModal(student);
    }, [removeStudentModal]);

    const confirmRemoveStudent = useCallback(() => {
        if (removeStudentModal.data) {
            router.delete(route('groups.remove-student', {
                group: group.id,
                student: removeStudentModal.data.id
            }), {
                onSuccess: () => {
                    removeStudentModal.closeModal();
                }
            });
        }
    }, [group.id, removeStudentModal.data, removeStudentModal.closeModal]);

    const handleBulkRemove = useCallback((_ids: (number | string)[]) => {
        bulkRemoveModal.openModal(null);
    }, [bulkRemoveModal]);

    const handleConfirmBulkRemove = useCallback(() => {
        if (selectedIds.length === 0) return;

        executeBulkAction(
            route('groups.bulk-remove-students', { group: group.id }),
            { student_ids: selectedIds },
            {
                onSuccess: () => bulkRemoveModal.closeModal(),
                onError: () => bulkRemoveModal.closeModal()
            }
        );
    }, [selectedIds, group.id, executeBulkAction, bulkRemoveModal]);

    return {
        canUpdateGroups,
        canDeleteGroups,
        canManageGroupStudents,
        deleteGroupModal,
        removeStudentModal,
        bulkRemoveModal,
        selectedIds,
        setSelectedIds,
        isLoading,
        handleEditGroup,
        handleAssignStudents,
        handleDeleteGroup,
        handleRemoveStudent,
        confirmRemoveStudent,
        handleBulkRemove,
        handleConfirmBulkRemove
    };

}

export default useShowGroup;