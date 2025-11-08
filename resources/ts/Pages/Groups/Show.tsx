import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { UserGroupIcon, UsersIcon, UserMinusIcon } from '@heroicons/react/24/outline';
import { Group, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import { formatDate } from '@/utils';
import { DataTableConfig } from '@/types/datatable';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section, StatCard, TextEntry } from '@/Components';
import { useShowGroup } from '@/hooks/features/groups';
import { getStudentStatusInfo } from '@/utils/formatting/formatters';

interface Props {
    group: Group;
    students: PaginationType<User & {
        pivot: Pivot;
    }>;
    filters: Filters;
    statistics: Statistics;
}

interface Pivot {
    enrolled_at: string;
    left_at?: string;
    is_active: boolean;
}

interface Statistics {
    total_students: number;
    active_students: number;
    inactive_students: number;
    available_slots: number;
}

interface Filters {
    search?: string;
    status?: string;
}

export default function ShowGroup({ group, students, filters: _filters, statistics }: Props) {

    const {
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
    } = useShowGroup(group);

    const studentsTableConfig: DataTableConfig<User & {
        pivot: { enrolled_at: string; left_at?: string; is_active: boolean }
    }> = {
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.groups.student'),
                render: (student) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{student.name}</div>
                        <div className="text-sm text-gray-500">{student.email}</div>
                    </div>
                )
            },
            {
                key: 'enrolled_at',
                label: trans('admin_pages.groups.enrolled_at'),
                render: (student) => (
                    <span className="text-sm text-gray-600">
                        {formatDate(student.pivot.enrolled_at)}
                    </span>
                )
            },
            {
                key: 'left_at',
                label: trans('admin_pages.groups.left_at'),
                render: (student) => (
                    <span className="text-sm text-gray-600">
                        {student.pivot.left_at ? formatDate(student.pivot.left_at) : '-'}
                    </span>
                )
            },
            {
                key: 'status',
                label: trans('admin_pages.common.status'),
                render: (student) => {
                    const statusInfo = getStudentStatusInfo(student.pivot.is_active);
                    return (
                        <Badge label={statusInfo.label} type={statusInfo.type} />
                    );
                }
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (student) => (
                    student.pivot.is_active && canManageGroupStudents ? (
                        <Button
                            onClick={() => handleRemoveStudent(student)}
                            color="secondary"
                            size="sm"
                            variant="outline"
                        >
                            {trans('admin_pages.groups.remove_button')}
                        </Button>
                    ) : null
                )
            }
        ],
        searchPlaceholder: trans('admin_pages.groups.search_student_placeholder'),
        filters: [
            {
                key: 'status',
                type: 'select',
                label: trans('admin_pages.groups.filter_by_status'),
                options: [
                    { label: trans('admin_pages.groups.all_statuses'), value: '' },
                    { label: trans('admin_pages.groups.active_only'), value: 'active' },
                    { label: trans('admin_pages.groups.inactive_only'), value: 'inactive' }
                ]
            }
        ],
        emptyState: {
            title: trans('admin_pages.groups.no_students_assigned'),
            subtitle: trans('admin_pages.groups.assign_students_to_start'),
            icon: <UserGroupIcon className="w-12 h-12 mx-auto text-gray-400" />
        },
        perPageOptions: [10, 25, 50],
        enableSelection: canManageGroupStudents,
        isSelectable: (student) => student.pivot.is_active,
        selectionActions: canManageGroupStudents ? (selectedIds) => (
            <>
                <Button
                    onClick={() => handleBulkRemove(selectedIds)}
                    color="danger"
                    size="sm"
                >
                    <UserMinusIcon className="w-4 h-4 mr-2" />
                    {trans('admin_pages.groups.bulk_remove_button', { count: selectedIds.length })}
                </Button>
            </>
        ) : undefined,
    };

    return (
        <AuthenticatedLayout title={group.display_name}
            breadcrumb={breadcrumbs.groupShow(group.display_name)}
        >
            <ConfirmationModal
                isOpen={deleteGroupModal.isOpen}
                onClose={deleteGroupModal.closeModal}
                onConfirm={handleDeleteGroup}
                title={trans('admin_pages.groups.delete_group_title')}
                message={trans('admin_pages.groups.delete_group_message', { name: group.display_name })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />

            <ConfirmationModal
                isOpen={removeStudentModal.isOpen}
                onClose={removeStudentModal.closeModal}
                onConfirm={confirmRemoveStudent}
                title={trans('admin_pages.groups.remove_student_title')}
                message={trans('admin_pages.groups.remove_student_message', { name: removeStudentModal.data?.name || '' })}
                confirmText={trans('admin_pages.groups.remove_button')}
                cancelText={trans('admin_pages.common.cancel')}
                type="warning"
            />


            <Section
                title={group.display_name}
                subtitle={group.description || trans('admin_pages.groups.group_details')}
                collapsible
                actions={(canManageGroupStudents || canUpdateGroups || canDeleteGroups) && (
                    <div className="flex space-x-2">
                        {canUpdateGroups && (
                            <Button
                                onClick={handleAssignStudents}
                                color="secondary"
                                variant="outline"
                                size="sm"
                            >
                                {trans('admin_pages.groups.assign_students_action')}
                            </Button>
                        )}
                        {canUpdateGroups && (
                            <Button
                                onClick={handleEditGroup}
                                color="primary"
                                variant="outline"
                                size="sm"
                            >
                                {trans('admin_pages.common.modify')}
                            </Button>
                        )}
                        {canDeleteGroups && (
                            <Button
                                onClick={() => deleteGroupModal.openModal(null)}
                                color="danger"
                                variant="outline"
                                size="sm"
                            >
                                {trans('admin_pages.common.delete')}
                            </Button>
                        )}
                    </div>
                )}
            >
                <h3 className="text-lg font-medium text-gray-900 mb-4">{trans('admin_pages.groups.group_information')}</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <TextEntry label={trans('admin_pages.groups.academic_level')} value={group.level?.name || '-'} />
                    <TextEntry label={trans('admin_pages.groups.academic_year')} value={group.academic_year ?? '-'} />
                    <TextEntry label={trans('admin_pages.groups.period')} value={`${formatDate(group.start_date)} - ${formatDate(group.end_date)}`} />
                    <TextEntry label={trans('admin_pages.groups.capacity')} value={trans('admin_pages.groups.max_students_label', { count: group.max_students })} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <StatCard
                        title={trans('admin_pages.groups.total_students')}
                        value={statistics.total_students}
                        icon={UserGroupIcon}
                        color="blue"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.active_students_label')}
                        value={statistics.active_students}
                        icon={UsersIcon}
                        color="green"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.stats_inactive')}
                        value={statistics.inactive_students}
                        icon={UsersIcon}
                        color="red"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.available_slots')}
                        value={statistics.available_slots}
                        icon={UsersIcon}
                        color="purple"
                    />
                </div>
            </Section>

            <Section title={trans('admin_pages.groups.students_list')}>
                <DataTable
                    data={students}
                    config={studentsTableConfig}
                    onSelectionChange={setSelectedIds}
                />
            </Section>

            <ConfirmationModal
                isOpen={bulkRemoveModal.isOpen}
                onClose={bulkRemoveModal.closeModal}
                onConfirm={handleConfirmBulkRemove}
                title={trans('admin_pages.groups.bulk_remove_title')}
                message={trans('admin_pages.groups.bulk_remove_message', { count: selectedIds.length })}
                confirmText={trans('admin_pages.groups.remove_button')}
                cancelText={trans('admin_pages.common.cancel')}
                type="warning"
                icon={UserMinusIcon}
                loading={isLoading}
            />
        </AuthenticatedLayout>
    );
}