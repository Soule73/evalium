import { UserGroupIcon } from '@heroicons/react/24/outline';
import { Exam, Group } from '@/types';
import { trans } from '@/utils';
import { ConfirmationModal } from '@/Components';

interface PendingAssignment {
    ids: (string | number)[];
    count: number;
}

interface AssignmentModalsProps {
    exam: Exam;
    showConfirmModal: boolean;
    showRemoveGroupModal: { isOpen: boolean; group: Group | null };
    pendingAssignment: PendingAssignment | null;
    isProcessing: boolean;
    onConfirmAssignment: () => void;
    onCancelAssignment: () => void;
    onConfirmRemove: () => void;
    onCancelRemove: () => void;
}

export function AssignmentModals({
    exam,
    showConfirmModal,
    showRemoveGroupModal,
    pendingAssignment,
    isProcessing,
    onConfirmAssignment,
    onCancelAssignment,
    onConfirmRemove,
    onCancelRemove
}: AssignmentModalsProps) {
    return (
        <>
            <ConfirmationModal
                isOpen={showConfirmModal}
                onClose={onCancelAssignment}
                onConfirm={onConfirmAssignment}
                title={trans('exam_pages.assign.confirm_title')}
                message={trans('exam_pages.assign.confirm_message', {
                    groups: pendingAssignment?.ids.length || 0,
                    students: pendingAssignment?.count || 0
                })}
                confirmText={trans('exam_pages.assign.confirm_button')}
                cancelText={trans('exam_pages.assign.cancel')}
                type="info"
                icon={UserGroupIcon}
                loading={isProcessing}
            >
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 w-full">
                    <p className="text-sm text-blue-800">
                        <strong>{trans('exam_pages.assign.exam_label')}</strong> {exam.title}
                    </p>
                    {exam.description && (
                        <p className="text-sm text-blue-700 mt-1">{exam.description}</p>
                    )}
                    <p className="text-sm text-blue-700 mt-1">
                        <strong>{trans('exam_pages.assign.duration_info')}</strong> {exam.duration} {trans('exam_pages.assign.minutes')}
                    </p>
                </div>
            </ConfirmationModal>

            <ConfirmationModal
                isOpen={showRemoveGroupModal.isOpen}
                onClose={onCancelRemove}
                onConfirm={onConfirmRemove}
                title={trans('exam_pages.show.remove_group_title')}
                message={trans('exam_pages.show.remove_group_message', {
                    exam: exam.title,
                    group: showRemoveGroupModal.group?.display_name || ''
                })}
                confirmText={trans('exam_pages.show.remove')}
                cancelText={trans('exam_pages.assign.cancel')}
                type="danger"
            />
        </>
    );
}
