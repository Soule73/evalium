import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { hasPermission } from '@/utils/permissions';
import { PageProps } from '@/types';
import { trans } from '@/utils/translations';
import { Badge, Button, ConfirmationModal, DataTable, Section, Toggle } from '@/Components';

interface Level {
    id: number;
    name: string;
    code: string;
    description: string | null;
    order: number;
    is_active: boolean;
    groups_count: number;
    active_groups_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    levels: PaginationType<Level>;
}

export default function LevelIndex({ levels }: Props) {
    const { auth } = usePage<PageProps>().props;

    // Vérification des permissions
    const canCreateLevels = hasPermission(auth.permissions, 'create levels');
    const canUpdateLevels = hasPermission(auth.permissions, 'update levels');
    const canDeleteLevels = hasPermission(auth.permissions, 'delete levels');

    const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; levelId: number | null; levelName: string }>({
        isOpen: false,
        levelId: null,
        levelName: ''
    });

    const handleCreate = () => {
        router.visit(route('levels.create'));
    };

    const handleEdit = (levelId: number) => {
        router.visit(route('levels.edit', { level: levelId }));
    };

    const handleToggleStatus = (levelId: number) => {
        router.patch(route('levels.toggle-status', { level: levelId }), {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (levelId: number) => {
        if (!deleteModal.levelId) return;
        router.delete(route('levels.destroy', { level: levelId }), {
            onFinish: () => setDeleteModal({ isOpen: false, levelId: null, levelName: '' })
        });
    };

    const dataTableConfig: DataTableConfig<Level> = {
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.levels.name'),
                render: (level) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{level.name}</div>
                        <div className="text-xs text-gray-500">{trans('admin_pages.levels.code')}: {level.code}</div>
                    </div>
                ),
                sortable: true,
            },
            {
                key: 'description',
                label: trans('admin_pages.levels.description'),
                render: (level) => (
                    <div className="text-sm text-gray-700 max-w-md truncate">
                        {level.description || '-'}
                    </div>
                ),
            },
            {
                key: 'order',
                label: trans('admin_pages.levels.order'),
                render: (level) => (
                    <Badge label={level.order.toString()} type="gray" />
                ),
                sortable: true,
            },
            {
                key: 'groups',
                label: trans('admin_pages.levels.groups_count'),
                render: (level) => (
                    <div className="text-sm">
                        <div className="text-gray-900">{level.groups_count}</div>
                        <div className="text-xs text-green-600">{trans('admin_pages.levels.active_groups', { active: level.active_groups_count })}</div>
                    </div>
                ),
            },
            {
                key: 'is_active',
                label: trans('admin_pages.levels.status'),
                render: (level) => (
                    canUpdateLevels ? (
                        <Toggle
                            checked={level.is_active}
                            onChange={() => handleToggleStatus(level.id)}
                            size="sm"
                        />
                    ) : (
                        <Badge
                            label={level.is_active ? trans('admin_pages.common.active') : trans('admin_pages.common.inactive')}
                            type={level.is_active ? 'success' : 'gray'}
                        />
                    )
                ),
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (level) => (canUpdateLevels || canDeleteLevels) ? (
                    <div className="flex gap-2">
                        {canUpdateLevels && (
                            <Button
                                onClick={() => handleEdit(level.id)}
                                size="sm"
                                color="primary"
                            >
                                {trans('admin_pages.common.edit')}
                            </Button>
                        )}
                        {canDeleteLevels && (
                            <Button
                                onClick={() => setDeleteModal({
                                    isOpen: true,
                                    levelId: level.id,
                                    levelName: level.name
                                })}
                                size="sm"
                                color="danger"
                                disabled={level.groups_count > 0}
                            >
                                {trans('admin_pages.common.delete')}
                            </Button>
                        )}
                    </div>
                ) : null,
            },
        ],
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.levels.title')}>
            <Section
                title={trans('admin_pages.levels.title')}
                subtitle={trans('admin_pages.levels.subtitle')}
                actions={canCreateLevels && (
                    <Button onClick={handleCreate} color="primary">
                        <PlusIcon className="w-5 h-5 mr-2" />
                        {trans('admin_pages.levels.create')}
                    </Button>
                )}
            >
                <DataTable
                    config={dataTableConfig}
                    data={levels}
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, levelId: null, levelName: '' })}
                onConfirm={() => deleteModal.levelId && handleDelete(deleteModal.levelId)}
                title={trans('admin_pages.levels.delete_title')}
                message={trans('admin_pages.levels.delete_message', { name: deleteModal.levelName })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
