import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { PlusIcon } from '@heroicons/react/24/outline';
import Toggle from '@/Components/form/Toggle';
import Badge from '@/Components/Badge';
import { useState } from 'react';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { hasPermission } from '@/utils/permissions';
import { PageProps } from '@/types';

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
                label: 'Nom',
                render: (level) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{level.name}</div>
                        <div className="text-xs text-gray-500">Code: {level.code}</div>
                    </div>
                ),
                sortable: true,
            },
            {
                key: 'description',
                label: 'Description',
                render: (level) => (
                    <div className="text-sm text-gray-700 max-w-md truncate">
                        {level.description || '-'}
                    </div>
                ),
            },
            {
                key: 'order',
                label: 'Ordre',
                render: (level) => (
                    <Badge label={level.order.toString()} type="gray" />
                ),
                sortable: true,
            },
            {
                key: 'groups',
                label: 'Groupes',
                render: (level) => (
                    <div className="text-sm">
                        <div className="text-gray-900">{level.groups_count} total</div>
                        <div className="text-xs text-green-600">{level.active_groups_count} actifs</div>
                    </div>
                ),
            },
            {
                key: 'is_active',
                label: 'Statut',
                render: (level) => (
                    canUpdateLevels ? (
                        <Toggle
                            checked={level.is_active}
                            onChange={() => handleToggleStatus(level.id)}
                            size="sm"
                        />
                    ) : (
                        <Badge
                            label={level.is_active ? 'Actif' : 'Inactif'}
                            type={level.is_active ? 'success' : 'gray'}
                        />
                    )
                ),
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (level) => (canUpdateLevels || canDeleteLevels) ? (
                    <div className="flex gap-2">
                        {canUpdateLevels && (
                            <Button
                                onClick={() => handleEdit(level.id)}
                                size="sm"
                                color="primary"
                            >
                                Modifier
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
                                Supprimer
                            </Button>
                        )}
                    </div>
                ) : null,
            },
        ],
    };

    return (
        <AuthenticatedLayout title="Gestion des niveaux">
            <Section
                title="Niveaux"
                subtitle="Liste des niveaux disponibles"
                actions={canCreateLevels && (
                    <Button onClick={handleCreate} color="primary">
                        <PlusIcon className="w-5 h-5 mr-2" />
                        Nouveau niveau
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
                title="Supprimer le niveau"
                message={`Êtes-vous sûr de vouloir supprimer le niveau "${deleteModal.levelName}" ?`}
                confirmText="Supprimer"
                cancelText="Annuler"
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
