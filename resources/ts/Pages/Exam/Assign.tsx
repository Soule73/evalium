import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, Group } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
import { useAssign } from '@/hooks/features/exams/useAssign';
import {
    ExamAssignInfoSection,
    AssignedGroupsSection,
    AvailableGroupsSection,
    AssignmentModals
} from '@/Components';

interface Props {
    exam: Exam;
    assignedGroups: PaginationType<Group>;
    availableGroups: PaginationType<Group>;
}

export default function Assign({ exam, assignedGroups, availableGroups }: Props) {
    const {
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
    } = useAssign({ exam, availableGroups });

    return (
        <AuthenticatedLayout
            title={trans('exam_pages.assign.title', { title: exam.title })}
            breadcrumb={breadcrumbs.examAssign(exam.title, exam.id)}
        >
            <ExamAssignInfoSection
                exam={exam}
                onCancel={handleCancelAssign}
            />

            <AssignedGroupsSection
                exam={exam}
                assignedGroups={assignedGroups}
                onRemoveGroup={openRemoveGroupModal}
            />

            <AvailableGroupsSection
                availableGroups={availableGroups}
                isProcessing={isProcessing}
                onAssignGroups={handleAssignGroups}
            />

            <AssignmentModals
                exam={exam}
                showConfirmModal={showConfirmModal}
                showRemoveGroupModal={showRemoveGroupModal}
                pendingAssignment={pendingAssignment}
                isProcessing={isProcessing}
                onConfirmAssignment={confirmAssignment}
                onCancelAssignment={cancelAssignment}
                onConfirmRemove={handleRemoveGroup}
                onCancelRemove={closeRemoveGroupModal}
            />
        </AuthenticatedLayout>
    );
}