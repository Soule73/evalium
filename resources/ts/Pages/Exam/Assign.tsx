import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import { Exam, Group } from '@/types';
import { route } from 'ziggy-js';
import { UserGroupIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import ConfirmationModal from '@/Components/ConfirmationModal';
import MarkdownRenderer from '@/Components/form/MarkdownRenderer';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';

interface Props {
    exam: Exam;
    assignedGroups: Group[];
    availableGroups: Group[];
}

export default function Assign({ exam, assignedGroups, availableGroups }: Props) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [showRemoveGroupModal, setShowRemoveGroupModal] = useState<{ isOpen: boolean; group: Group | null }>({
        isOpen: false,
        group: null
    });
    const [pendingAssignment, setPendingAssignment] = useState<{
        ids: (string | number)[];
        count: number;
    } | null>(null);

    const handleAssignGroups = (selectedIds: (string | number)[]) => {
        const selectedGroups = availableGroups.filter(g => selectedIds.includes(g.id));
        const totalStudents = selectedGroups.reduce((sum, group) => sum + (group.active_students_count || 0), 0);

        setPendingAssignment({
            ids: selectedIds,
            count: totalStudents
        });
        setShowConfirmModal(true);
    };

    const confirmAssignment = () => {
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
    };

    const cancelAssignment = () => {
        setShowConfirmModal(false);
        setPendingAssignment(null);
    };

    const handleRemoveGroup = () => {
        if (!showRemoveGroupModal.group) return;

        router.delete(
            route('exams.groups.remove', { exam: exam.id, group: showRemoveGroupModal.group.id }),
            {
                onFinish: () => setShowRemoveGroupModal({ isOpen: false, group: null })
            }
        );
    };

    // Transformer availableGroups en format PaginationType
    const groupsData: PaginationType<Group> = {
        data: availableGroups,
        current_page: 1,
        per_page: availableGroups.length,
        total: availableGroups.length,
        last_page: 1,
        from: 1,
        to: availableGroups.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    const groupsTableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'display_name',
                label: trans('exam_pages.common.group'),
                render: (group) => (
                    <div className="flex items-center space-x-3">
                        <UserGroupIcon className="h-5 w-5 text-gray-400 shrink-0" />
                        <div>
                            <div className="font-medium text-gray-900">{group.display_name}</div>
                            <div className="text-sm text-gray-500">
                                {trans('exam_pages.assign.active_students', { count: group.active_students_count || 0 })}
                            </div>
                        </div>
                    </div>
                )
            }
        ],
        searchPlaceholder: trans('exam_pages.assign.search_placeholder'),
        emptyState: {
            title: trans('exam_pages.assign.all_assigned'),
            subtitle: trans('exam_pages.assign.all_assigned_subtitle'),
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: trans('exam_pages.assign.no_groups_found'),
            subtitle: trans('exam_pages.assign.no_groups_found_subtitle'),
            resetLabel: trans('exam_pages.assign.reset_search')
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        selectionActions: (selectedIds) => {
            const plural = selectedIds.length > 1 ? trans('exam_pages.common.s') : '';
            return (
                <Button
                    onClick={() => handleAssignGroups(selectedIds)}
                    color="primary"
                    size="sm"
                    loading={isProcessing}
                    disabled={isProcessing || selectedIds.length === 0}
                >
                    <UserPlusIcon className="w-4 h-4 mr-2" />
                    {trans('exam_pages.assign.assign_to_groups', { count: selectedIds.length, plural })}
                </Button>
            );
        },
    };

    return (
        <AuthenticatedLayout title={trans('exam_pages.assign.title', { title: exam.title })}
            breadcrumb={breadcrumbs.examAssign(exam.title, exam.id)}
        >

            <Section
                title={trans('exam_pages.assign.exam_info')}
                subtitle={trans('exam_pages.assign.exam_info_subtitle')}
                actions={<Button
                    type="button"
                    onClick={() => router.visit(route('exams.show', exam.id))}
                    color="secondary"
                    variant="outline"
                >
                    {trans('exam_pages.assign.cancel')}
                </Button>}
            >
                <div className="space-y-2">
                    <h2 className="text-xl font-semibold text-gray-900">{exam.title}</h2>
                    {exam.description && (
                        <MarkdownRenderer>{exam.description}</MarkdownRenderer>
                    )}
                    <p className="text-sm text-gray-500">
                        {trans('exam_pages.assign.duration_label', { duration: exam.duration })}
                    </p>
                </div>
            </Section>

            {/* Groupes déjà assignés */}
            {assignedGroups.length > 0 && (
                <Section
                    title={trans('exam_pages.assign.assigned_groups_title')}
                    subtitle={trans('exam_pages.assign.assigned_groups_subtitle', { count: assignedGroups.length })}
                    collapsible
                    defaultOpen={false}
                >
                    <DataTable
                        data={{
                            data: assignedGroups,
                            current_page: 1,
                            per_page: assignedGroups.length,
                            total: assignedGroups.length,
                            last_page: 1,
                            from: 1,
                            to: assignedGroups.length,
                            first_page_url: '',
                            last_page_url: '',
                            next_page_url: null,
                            prev_page_url: null,
                            path: '',
                            links: []
                        }}
                        config={{
                            columns: [
                                {
                                    key: 'display_name',
                                    label: trans('exam_pages.common.group'),
                                    render: (group) => (
                                        <div className="flex items-center space-x-3">
                                            <UserGroupIcon className="h-5 w-5 text-green-600 shrink-0" />
                                            <div>
                                                <div className="font-medium text-gray-900">{group.display_name}</div>
                                                <div className="text-sm text-gray-500">
                                                    {trans('exam_pages.assign.active_students', { count: group.active_students_count || 0 })}
                                                </div>
                                            </div>
                                        </div>
                                    )
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    render: (group) => (
                                        <div className="flex space-x-2">
                                            <Button
                                                type="button"
                                                color="primary"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(route('exams.group.show', { exam: exam.id, group: group.id }))}
                                            >
                                                {trans('exam_pages.show.view_details')}
                                            </Button>
                                            <Button
                                                type="button"
                                                color="danger"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setShowRemoveGroupModal({ isOpen: true, group })}
                                            >
                                                {trans('exam_pages.show.remove')}
                                            </Button>
                                        </div>
                                    )
                                }
                            ],
                            searchPlaceholder: trans('exam_pages.assign.search_placeholder'),
                            emptyState: {
                                title: 'Aucun groupe assigné',
                                subtitle: 'Aucun groupe n\'a encore accès à cet examen',
                                icon: 'UserGroupIcon'
                            },
                            perPageOptions: [10, 25, 50],
                        }}
                    />
                </Section>
            )}

            {/* Sélectionner les groupes à assigner */}
            <Section
                title={trans('exam_pages.assign.select_groups_title')}
                subtitle={trans('exam_pages.assign.select_groups_subtitle')}
            >
                <DataTable
                    data={groupsData}
                    config={groupsTableConfig}
                />
            </Section>

            {/* Modal de confirmation */}
            <ConfirmationModal
                isOpen={showConfirmModal}
                onClose={cancelAssignment}
                onConfirm={confirmAssignment}
                title={trans('exam_pages.assign.confirm_title')}
                message={trans('exam_pages.assign.confirm_message', { groups: pendingAssignment?.ids.length || 0, students: pendingAssignment?.count || 0 })}
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
                onClose={() => setShowRemoveGroupModal({ isOpen: false, group: null })}
                onConfirm={handleRemoveGroup}
                title={trans('exam_pages.show.remove_group_title')}
                message={trans('exam_pages.show.remove_group_message', { exam: exam.title, group: showRemoveGroupModal.group?.display_name || '' })}
                confirmText={trans('exam_pages.show.remove')}
                cancelText={trans('exam_pages.assign.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}