import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { UserGroupIcon, UsersIcon, UserMinusIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { Group, User, PageProps } from '@/types';
import { formatDate } from '@/utils/formatters';
import { DataTableConfig } from '@/types/datatable';
import { useState } from 'react';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { hasPermission } from '@/utils/permissions';
import { trans } from '@/utils/translations';
import { Badge, Button, ConfirmationModal, DataTable, Section, StatCard, TextEntry } from '@/Components';

interface Props {
    group: Group & {
        students: Array<User & {
            pivot: {
                enrolled_at: string;
                left_at?: string;
                is_active: boolean;
            }
        }>;
    };
}

export default function ShowGroup({ group }: Props) {
    const { auth } = usePage<PageProps>().props;

    const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
    const canDeleteGroups = hasPermission(auth.permissions, 'delete groups');
    const canManageGroupStudents = hasPermission(auth.permissions, 'manage students');

    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [studentToRemove, setStudentToRemove] = useState<User | null>(null);
    const [selectedStudents, setSelectedStudents] = useState<(number | string)[]>([]);
    const [confirmBulkRemove, setConfirmBulkRemove] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleEditGroup = () => {
        router.visit(route('groups.edit', { group: group.id }));
    };

    const handleAssignStudents = () => {
        router.visit(route('groups.assign-students', { group: group.id }));
    };

    const handleDeleteGroup = () => {
        router.delete(route('groups.destroy', { group: group.id }), {
            onSuccess: () => {
                router.visit(route('groups.index'));
            }
        });
    };

    const handleRemoveStudent = (student: User) => {
        setStudentToRemove(student);
    };

    const confirmRemoveStudent = () => {
        if (studentToRemove) {
            router.delete(route('groups.remove-student', {
                group: group.id,
                student: studentToRemove.id
            }), {
                onSuccess: () => {
                    setStudentToRemove(null);
                }
            });
        }
    };

    const handleBulkRemove = (_ids: (number | string)[]) => {
        setConfirmBulkRemove(true);
    };

    const handleConfirmBulkRemove = () => {
        if (selectedStudents.length === 0) return;

        setLoading(true);
        router.post(route('groups.bulk-remove-students', { group: group.id }), {
            student_ids: selectedStudents
        }, {
            onSuccess: () => {
                setSelectedStudents([]);
                setConfirmBulkRemove(false);
                setLoading(false);
            },
            onError: () => {
                setLoading(false);
                setConfirmBulkRemove(false);
            }
        });
    };

    const handleCloseBulkModal = () => {
        if (!loading) {
            setConfirmBulkRemove(false);
        }
    };


    const getStudentStatusBadge = (isActive: boolean) => {
        return isActive ? (
            <Badge label={trans('admin_pages.groups.enrolled')} type="success" />
        ) : (
            <Badge label={trans('admin_pages.groups.left')} type="gray" />
        );
    };

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
                render: (student) => getStudentStatusBadge(student.pivot.is_active)
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

    // Convertir les données pour la table
    const studentsData = {
        data: group.students || [],
        current_page: 1,
        last_page: 1,
        per_page: group.students?.length || 0,
        total: group.students?.length || 0,
        from: 1,
        to: group.students?.length || 0,
        first_page_url: '',
        last_page_url: '',
        links: [],
        next_page_url: null,
        path: '',
        prev_page_url: null
    };

    // Calculs statistiques
    const totalStudents = group.students?.length || 0;
    const activeStudents = group.students?.filter(student => student.pivot.is_active).length || 0;
    const inactiveStudents = group.students?.filter(student => !student.pivot.is_active).length || 0;

    return (
        <AuthenticatedLayout title={group.display_name}
            breadcrumb={breadcrumbs.groupShow(group.display_name)}
        >
            <ConfirmationModal
                isOpen={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDeleteGroup}
                title={trans('admin_pages.groups.delete_group_title')}
                message={trans('admin_pages.groups.delete_group_message', { name: group.display_name })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />

            <ConfirmationModal
                isOpen={!!studentToRemove}
                onClose={() => setStudentToRemove(null)}
                onConfirm={confirmRemoveStudent}
                title={trans('admin_pages.groups.remove_student_title')}
                message={trans('admin_pages.groups.remove_student_message', { name: studentToRemove?.name || '' })}
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
                                onClick={() => setShowDeleteModal(true)}
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
                {/* Informations générales */}
                <h3 className="text-lg font-medium text-gray-900 mb-4">{trans('admin_pages.groups.group_information')}</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <TextEntry label={trans('admin_pages.groups.academic_level')} value={group.level?.name || '-'} />
                    <TextEntry label={trans('admin_pages.groups.academic_year')} value={group.academic_year ?? '-'} />
                    <TextEntry label={trans('admin_pages.groups.period')} value={`${formatDate(group.start_date)} - ${formatDate(group.end_date)}`} />
                    <TextEntry label={trans('admin_pages.groups.capacity')} value={trans('admin_pages.groups.max_students_label', { count: group.max_students })} />
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <StatCard
                        title={trans('admin_pages.groups.total_students')}
                        value={totalStudents}
                        icon={UserGroupIcon}
                        color="blue"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.active_students_label')}
                        value={activeStudents}
                        icon={UsersIcon}
                        color="green"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.stats_inactive')}
                        value={inactiveStudents}
                        icon={UsersIcon}
                        color="red"
                    />
                    <StatCard
                        title={trans('admin_pages.groups.available_slots')}
                        value={Math.max(0, group.max_students - activeStudents)}
                        icon={UsersIcon}
                        color="purple"
                    />
                </div>
            </Section>

            {/* Liste des étudiants */}
            <Section title={trans('admin_pages.groups.students_list')}>
                <DataTable
                    data={studentsData}
                    config={studentsTableConfig}
                    onSelectionChange={(selectedIds) => {
                        setSelectedStudents(selectedIds);
                    }}
                />
            </Section>

            <ConfirmationModal
                isOpen={confirmBulkRemove}
                onClose={handleCloseBulkModal}
                onConfirm={handleConfirmBulkRemove}
                title={trans('admin_pages.groups.bulk_remove_title')}
                message={trans('admin_pages.groups.bulk_remove_message', { count: selectedStudents.length })}
                confirmText={trans('admin_pages.groups.remove_button')}
                cancelText={trans('admin_pages.common.cancel')}
                type="warning"
                icon={UserMinusIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}