import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate } from '@/utils/formatters';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { UserGroupIcon, AcademicCapIcon, UsersIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { Group, PageProps } from '@/types';
import Badge from '@/Components/Badge';
import { useState } from 'react';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { hasPermission } from '@/utils/permissions';
import { trans } from '@/utils/translations';

interface Props extends PageProps {
    groups: PaginationType<Group>;
    filters: {
        search?: string;
        level_id?: string;
        is_active?: string;
    };
    levels: Record<number, string>;
}

export default function GroupIndex({ groups, levels }: Props) {
    const { auth } = usePage<PageProps>().props;

    // Vérifications des permissions
    const canCreateGroups = hasPermission(auth.permissions, 'create groups');
    const canViewGroups = hasPermission(auth.permissions, 'view groups');
    const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
    const canToggleStatus = hasPermission(auth.permissions, 'toggle group status');

    const [confirmModal, setConfirmModal] = useState<{
        isOpen: boolean;
        type: 'activate' | 'deactivate' | null;
        ids: (number | string)[];
    }>({ isOpen: false, type: null, ids: [] });
    const [loading, setLoading] = useState(false);

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
        setConfirmModal({ isOpen: true, type: 'activate', ids });
    };

    const handleBulkDeactivate = (ids: (number | string)[]) => {
        setConfirmModal({ isOpen: true, type: 'deactivate', ids });
    };

    const handleConfirmAction = () => {
        if (!confirmModal.type) return;

        setLoading(true);
        const routeName = confirmModal.type === 'activate'
            ? 'groups.bulk-activate'
            : 'groups.bulk-deactivate';

        router.post(route(routeName), {
            ids: confirmModal.ids
        }, {
            onSuccess: () => {
                setConfirmModal({ isOpen: false, type: null, ids: [] });
                setLoading(false);
            },
            onError: () => {
                setLoading(false);
                setConfirmModal({ isOpen: false, type: null, ids: [] });
            }
        });
    };

    const handleCloseModal = () => {
        if (!loading) {
            setConfirmModal({ isOpen: false, type: null, ids: [] });
        }
    };

    const getStatusBadge = (isActive: boolean) => {
        return isActive ? (
            <Badge label={trans('admin_pages.common.active')} type="success" />
        ) : (
            <Badge label={trans('admin_pages.common.inactive')} type="error" />
        );
    };

    const dataTableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.groups.display_name'),
                render: (group) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{group.display_name}</div>
                        <div className="text-sm text-gray-500">{group.description || trans('admin_pages.groups.description')}</div>
                    </div>
                )
            },
            {
                key: 'level',
                label: trans('admin_pages.groups.level'),
                render: (group) => (
                    <div className="flex items-center">
                        <AcademicCapIcon className="w-4 h-4 mr-2 text-gray-400" />
                        <span className="text-sm text-gray-900">
                            {group.level?.name || trans('admin_pages.groups.level')}
                        </span>
                    </div>
                )
            },
            {
                key: 'students',
                label: trans('admin_pages.groups.students'),
                render: (group) => (
                    <div className="flex items-center">
                        <UsersIcon className="w-4 h-4 mr-2 text-gray-400" />
                        <span className="text-sm text-gray-600">
                            {group.active_students_count || 0} / {group.max_students}
                        </span>
                    </div>
                )
            },
            {
                key: 'period',
                label: trans('admin_pages.groups.period'),
                render: (group) => (
                    <div className="text-sm text-gray-600">
                        <div>{formatDate(group.start_date)}</div>
                        <div className="text-xs text-gray-400">{formatDate(group.end_date)}</div>
                    </div>
                )
            },
            {
                key: 'is_active',
                label: trans('admin_pages.groups.status'),
                render: (group) => getStatusBadge(group.is_active)
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (group) => (
                    <div className="flex space-x-2">
                        {canViewGroups && (
                            <Button
                                onClick={() => handleViewGroup(group.id)}
                                color="secondary"
                                size="sm"
                                variant="outline"
                            >
                                {trans('admin_pages.common.view')}
                            </Button>
                        )}
                        {canUpdateGroups && (
                            <Button
                                onClick={() => handleEditGroup(group.id)}
                                color="primary"
                                size="sm"
                                variant="outline"
                            >
                                {trans('admin_pages.common.edit')}
                            </Button>
                        )}
                    </div>
                )
            }
        ],
        searchPlaceholder: trans('admin_pages.groups.search_placeholder'),
        filters: [
            {
                key: 'level_id',
                type: 'select',
                label: trans('admin_pages.groups.filter_level'),
                options: [{ label: trans('admin_pages.groups.all_levels'), value: '' }].concat(
                    Object.entries(levels).map(([id, name]) => ({
                        label: name,
                        value: id
                    }))
                )
            },
            {
                key: 'is_active',
                type: 'select',
                label: trans('admin_pages.groups.filter_status'),
                options: [
                    { label: trans('admin_pages.groups.all_statuses'), value: '' },
                    { label: trans('admin_pages.common.active'), value: '1' },
                    { label: trans('admin_pages.common.inactive'), value: '0' }
                ]
            }
        ],
        emptyState: {
            title: trans('admin_pages.groups.empty_title'),
            subtitle: trans('admin_pages.groups.empty_subtitle'),
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: trans('admin_pages.groups.empty_title'),
            subtitle: trans('admin_pages.groups.empty_subtitle'),
            resetLabel: trans('admin_pages.common.cancel')
        },
        perPageOptions: [10, 25, 50],
        enableSelection: canToggleStatus,
        selectionActions: canToggleStatus ? (selectedIds) => (
            <>
                <Button
                    size="sm"
                    onClick={() => handleBulkActivate(selectedIds)}
                    variant="outline"
                    color="success"
                >
                    {trans('admin_pages.groups.activate')} ({selectedIds.length})
                </Button>
                <Button
                    size="sm"
                    onClick={() => handleBulkDeactivate(selectedIds)}
                    variant="outline"
                    color="danger"
                >
                    {trans('admin_pages.groups.deactivate')} ({selectedIds.length})
                </Button>
            </>
        ) : undefined,
    };

    // Calcul des statistiques
    const totalGroups = groups.total;
    const activeGroups = groups.data.filter(group => group.is_active).length;
    const totalStudents = groups.data.reduce((sum, group) => sum + (group.active_students_count || 0), 0);
    const averageStudentsPerGroup = totalGroups > 0 ? Math.round(totalStudents / totalGroups) : 0;

    return (
        <AuthenticatedLayout title={trans('admin_pages.groups.title')}
            breadcrumb={breadcrumbs.groups()}
        >
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title={trans('admin_pages.groups.stats_total')}
                    value={totalGroups}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <StatCard
                    title={trans('admin_pages.groups.stats_active')}
                    value={activeGroups}
                    icon={UserGroupIcon}
                    color="green"
                />
                <StatCard
                    title={trans('admin_pages.groups.stats_students')}
                    value={totalStudents}
                    icon={UsersIcon}
                    color="purple"
                />
                <StatCard
                    title={trans('admin_pages.groups.students')}
                    value={averageStudentsPerGroup}
                    icon={AcademicCapIcon}
                    color="yellow"
                />
            </div>

            <Section title={trans('admin_pages.groups.title')}
                subtitle={trans('admin_pages.groups.subtitle')}
                actions={canCreateGroups && (
                    <Button
                        onClick={handleCreateGroup}
                        color="primary"
                        variant="solid"
                        size="sm"
                    >
                        {trans('admin_pages.groups.create')}
                    </Button>
                )}
            >
                <DataTable
                    data={groups}
                    config={dataTableConfig}
                />
            </Section>

            <ConfirmationModal
                isOpen={confirmModal.isOpen}
                onClose={handleCloseModal}
                onConfirm={handleConfirmAction}
                title={confirmModal.type === 'activate' ? trans('admin_pages.groups.bulk_activate_title') : trans('admin_pages.groups.bulk_deactivate_title')}
                message={
                    confirmModal.type === 'activate'
                        ? trans('admin_pages.groups.bulk_activate_message', { count: confirmModal.ids.length })
                        : trans('admin_pages.groups.bulk_deactivate_message', { count: confirmModal.ids.length })
                }
                confirmText={trans('admin_pages.common.confirm')}
                cancelText={trans('admin_pages.common.cancel')}
                type={confirmModal.type === 'activate' ? 'info' : 'warning'}
                icon={confirmModal.type === 'activate' ? CheckCircleIcon : XCircleIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}