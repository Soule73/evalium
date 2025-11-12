import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Exam, Group } from '@/types';

interface PendingAssignment {
    ids: (string | number)[];
    count: number;
}

interface RemoveGroupModal {
    isOpen: boolean;
    group: Group | null;
}

interface UseAssignParams {
    exam: Exam;
    availableGroups: { data: Group[] };
}

interface UseAssignReturn {
    isProcessing: boolean;
    showConfirmModal: boolean;
    showRemoveGroupModal: RemoveGroupModal;
    pendingAssignment: PendingAssignment | null;
    handleAssignGroups: (selectedIds: (string | number)[]) => void;
    confirmAssignment: () => void;
    cancelAssignment: () => void;
    openRemoveGroupModal: (group: Group) => void;
    closeRemoveGroupModal: () => void;
    handleRemoveGroup: () => void;
    handleCancelAssign: () => void;
}

export function useAssign({ exam, availableGroups }: UseAssignParams): UseAssignReturn {
    const [isProcessing, setIsProcessing] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [showRemoveGroupModal, setShowRemoveGroupModal] = useState<RemoveGroupModal>({
        isOpen: false,
        group: null
    });
    const [pendingAssignment, setPendingAssignment] = useState<PendingAssignment | null>(null);

    const handleAssignGroups = useCallback((selectedIds: (string | number)[]) => {
        const selectedGroups = availableGroups.data.filter((g: Group) => selectedIds.includes(g.id));
        const totalStudents = selectedGroups.reduce((sum: number, group: Group) =>
            sum + (group.active_students_count || 0), 0
        );

        setPendingAssignment({
            ids: selectedIds,
            count: totalStudents
        });
        setShowConfirmModal(true);
    }, [availableGroups.data]);

    const confirmAssignment = useCallback(() => {
        if (!pendingAssignment) return;

        setIsProcessing(true);

        router.post(
            route('exams.assign.groups', exam.id),
            { group_ids: pendingAssignment.ids },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setShowConfirmModal(false);
                    setPendingAssignment(null);
                }
            }
        );
    }, [exam.id, pendingAssignment]);

    const cancelAssignment = useCallback(() => {
        setShowConfirmModal(false);
        setPendingAssignment(null);
    }, []);

    const openRemoveGroupModal = useCallback((group: Group) => {
        setShowRemoveGroupModal({ isOpen: true, group });
    }, []);

    const closeRemoveGroupModal = useCallback(() => {
        setShowRemoveGroupModal({ isOpen: false, group: null });
    }, []);

    const handleRemoveGroup = useCallback(() => {
        if (!showRemoveGroupModal.group) return;

        router.delete(
            route('exams.groups.remove', { exam: exam.id, group: showRemoveGroupModal.group.id }),
            {
                onFinish: closeRemoveGroupModal
            }
        );
    }, [exam.id, showRemoveGroupModal.group, closeRemoveGroupModal]);

    const handleCancelAssign = useCallback(() => {
        router.visit(route('exams.show', exam.id));
    }, [exam.id]);

    return {
        isProcessing,
        showConfirmModal,
        showRemoveGroupModal,
        pendingAssignment,
        handleAssignGroups,
        confirmAssignment,
        cancelAssignment,
        openRemoveGroupModal,
        closeRemoveGroupModal,
        handleRemoveGroup,
        handleCancelAssign
    };
}
